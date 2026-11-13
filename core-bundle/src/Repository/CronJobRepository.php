<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Repository;

use Contao\CoreBundle\Entity\CronJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\LockWaitTimeoutException;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<CronJob>
 *
 * @method CronJob|null findOneByName(string $name)
 *
 * @internal
 */
class CronJobRepository extends ServiceEntityRepository
{
    private readonly Connection $connection;

    private int|null $previousAutoCommit = null;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronJob::class);

        /** @var Connection $connection */
        $connection = $registry->getConnection();

        $this->connection = $connection;
    }

    /**
     * Locks the tl_cron_job table with a lock wait timeout of only 1 second.
     *
     * @throws LockWaitTimeoutException
     */
    public function lockTable(): void
    {
        $table = $this->getClassMetadata()->getTableName();

        $defaultLockTimeout = $this->connection->fetchOne('SELECT @@lock_wait_timeout');

        // Use default lock timeout from MariaDB, if it cannot be retrieved
        if (false === $defaultLockTimeout) {
            $defaultLockTimeout = 86400;
        }

        if ($this->connection->isTransactionActive()) {
            throw new \LogicException('Cannot acquire the cron table lock within an active transaction.');
        }

        $autoCommit = (int) $this->connection->fetchOne('SELECT @@autocommit');

        try {
            try {
                $this->connection->executeStatement('SET SESSION lock_wait_timeout = 1');
                $this->connection->executeStatement('SET SESSION autocommit = 0');

                // Begin first, so Doctrine's flush() can nest its own transaction
                $this->connection->beginTransaction();

                // LOCK TABLES commits implicitly, then InnoDB starts a new transaction
                $this->connection->executeStatement("LOCK TABLES $table WRITE, $table AS t0 WRITE, $table AS t0_ WRITE");
            } finally {
                $this->connection->executeStatement('SET SESSION lock_wait_timeout = ?', [$defaultLockTimeout], [Types::INTEGER]);
            }
        } catch (\Throwable $e) {
            try {
                if ($this->connection->isTransactionActive()) {
                    // A failed LOCK TABLES also commits the original transaction
                    $this->connection->executeStatement('START TRANSACTION');
                    $this->connection->rollBack();
                }
            } finally {
                $this->connection->executeStatement('SET SESSION autocommit = ?', [$autoCommit], [Types::INTEGER]);
            }

            throw $e;
        }

        $this->previousAutoCommit = $autoCommit;
    }

    /**
     * Commits or rolls back the transaction before releasing the table lock.
     */
    public function unlockTable(bool $commit = true): void
    {
        if (null === $this->previousAutoCommit) {
            return;
        }

        try {
            try {
                if ($commit) {
                    if (1 !== $this->connection->getTransactionNestingLevel()) {
                        throw new \LogicException('The cron table lock transaction is unbalanced.');
                    }

                    $this->connection->commit();
                } else {
                    while ($this->connection->isTransactionActive()) {
                        $this->connection->rollBack();
                    }
                }
            } catch (\Throwable $e) {
                while ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }

                throw $e;
            }
        } finally {
            try {
                $this->connection->executeStatement('UNLOCK TABLES');
            } finally {
                $autoCommit = $this->previousAutoCommit;
                $this->previousAutoCommit = null;
                $this->connection->executeStatement('SET SESSION autocommit = ?', [$autoCommit], [Types::INTEGER]);
            }
        }
    }

    /**
     * Purges cron job entries where lastRun is older than 1 year.
     *
     * @param list<string> $keepByName the cron job entries to keep by name
     */
    public function purgeOldRecords(array $keepByName = []): void
    {
        $qb = $this->createQueryBuilder('c');
        $qb
            ->delete()
            ->where('c.lastRun < :date')
            // Use a grace period of 1 month, so that a yearly cronjob is not deleted immediately
            ->setParameter('date', new \DateTimeImmutable('-1 year -1 month'))
        ;

        if ($keepByName) {
            $qb
                ->andWhere($qb->expr()->notIn('c.name', ':keepByName'))
                ->setParameter('keepByName', $keepByName, ArrayParameterType::STRING)
            ;
        }

        $qb->getQuery()->execute();
    }
}

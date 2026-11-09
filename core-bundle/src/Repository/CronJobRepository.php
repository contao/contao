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
use Contao\CoreBundle\Exception\LockAcquireTimeoutException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
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

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronJob::class);

        /** @var Connection $connection */
        $connection = $registry->getConnection();

        $this->connection = $connection;
    }

    /**
     * Acquires a database-scoped cron lock that survives transactions, waiting at
     * most 1 second.
     *
     * @throws LockAcquireTimeoutException
     */
    public function lockTable(): void
    {
        $locked = $this->connection->fetchOne('SELECT GET_LOCK(?, 1)', [$this->getLockName()]);

        if (null === $locked) {
            throw new \RuntimeException('An error occurred while acquiring the cron lock.');
        }

        if (1 !== (int) $locked) {
            throw new LockAcquireTimeoutException('Timed out waiting for the cron lock.');
        }
    }

    public function unlockTable(): void
    {
        $this->connection->fetchOne('SELECT RELEASE_LOCK(?)', [$this->getLockName()]);
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

    private function getLockName(): string
    {
        return 'contao.cron.'.hash('xxh3', $this->connection->getDatabase().'.'.$this->getClassMetadata()->getTableName());
    }
}

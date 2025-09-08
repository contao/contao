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

        try {
            // Set a short lock timeout, so that the next statement throws an exception sooner
            $this->connection->executeStatement('SET SESSION lock_wait_timeout = 1');
            $this->connection->executeStatement("LOCK TABLES $table WRITE, $table AS t0 WRITE, $table AS t0_ WRITE");
        } finally {
            // Restore the previous lock timeout
            $this->connection->executeStatement('SET SESSION lock_wait_timeout = ?', [$defaultLockTimeout], [Types::INTEGER]);
        }
    }

    public function unlockTable(): void
    {
        $this->connection->executeStatement('UNLOCK TABLES');
    }
}

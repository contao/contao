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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<CronJob>
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

    public function findOneByName(string $name): CronJob|null
    {
        return $this->createQueryBuilder('c')
            ->where('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return int|null Integer ID on success, null on failure
     */
    public function createJob(string $name, \DateTimeInterface $lastRun): int|null
    {
        try {
            $this->connection->insert('tl_cron_job', ['name' => $name, 'lastRun' => $lastRun], ['name' => Types::STRING, 'lastRun' => Types::DATETIME_MUTABLE]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        return (int) $this->connection->lastInsertId();
    }

    public function updateLastRun(int $id, \DateTimeInterface $lastRun, \DateTimeInterface|null $previous = null): bool
    {
        $sql = 'UPDATE tl_cron_job SET lastRun = :lastRun WHERE id = :id';
        $parameters = ['id' => $id, 'lastRun' => $lastRun];
        $types = ['id' => Types::INTEGER, 'lastRun' => Types::DATETIME_MUTABLE];

        if ($previous) {
            $sql .= ' AND lastRun = :previous';
            $parameters['previous'] = $previous;
            $types['previous'] = Types::DATETIME_MUTABLE;
        }

        return 1 === $this->connection->executeStatement($sql, $parameters, $types);
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

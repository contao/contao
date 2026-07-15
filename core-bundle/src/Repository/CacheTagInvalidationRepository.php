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

use Contao\CoreBundle\Entity\CacheTagInvalidation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<CacheTagInvalidation>
 *
 * @internal
 */
class CacheTagInvalidationRepository extends ServiceEntityRepository
{
    private readonly Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CacheTagInvalidation::class);

        /** @var Connection $connection */
        $connection = $registry->getConnection();

        $this->connection = $connection;
    }

    /**
     * @param list<string> $tags
     */
    public function schedule(array $tags, \DateTimeInterface $invalidateAt, string|null $identifier = null): void
    {
        $table = $this->getClassMetadata()->getTableName();
        $invalidateAt = \DateTimeImmutable::createFromInterface($invalidateAt)->setTimezone(new \DateTimeZone('UTC'));

        $this->connection->transactional(
            function () use ($table, $tags, $invalidateAt, $identifier): void {
                $this->connection
                    ->createQueryBuilder()
                    ->insert($table)
                    ->values([
                        'identifier' => ':identifier',
                        'tags' => ':tags',
                        'invalidateAt' => ':invalidateAt',
                    ])
                    ->setParameter('identifier', $identifier, Types::STRING)
                    ->setParameter('tags', $tags, Types::JSON)
                    ->setParameter('invalidateAt', $invalidateAt, Types::DATETIME_IMMUTABLE)
                    ->executeStatement()
                ;
            },
        );
    }

    public function cancel(string $identifier): void
    {
        $this->connection
            ->createQueryBuilder()
            ->delete($this->getClassMetadata()->getTableName())
            ->where('identifier = :identifier')
            ->setParameter('identifier', $identifier, Types::STRING)
            ->executeStatement()
        ;
    }

    /**
     * @return list<CacheTagInvalidation>
     */
    public function findDue(\DateTimeInterface $now): array
    {
        $now = \DateTimeImmutable::createFromInterface($now)->setTimezone(new \DateTimeZone('UTC'));

        return $this->createQueryBuilder('i')
            ->where('i.invalidateAt <= :now')
            ->setParameter('now', $now)
            ->orderBy('i.invalidateAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param list<int> $ids
     */
    public function removeByIds(array $ids): void
    {
        if ([] === $ids) {
            return;
        }

        $this->connection
            ->createQueryBuilder()
            ->delete($this->getClassMetadata()->getTableName())
            ->where('id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER)
            ->executeStatement()
        ;
    }
}

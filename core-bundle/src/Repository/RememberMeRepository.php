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

use Contao\CoreBundle\Entity\RememberMe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DoctrineType;

class RememberMeRepository extends ServiceEntityRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RememberMe::class);

        if (($connection = $registry->getConnection()) instanceof Connection) {
            $this->connection = $connection;
        }
    }

    public function lockTable()
    {
        $table = $this->getClassMetadata()->getTableName();

        $this->connection->exec("LOCK TABLES $table WRITE");
    }

    public function unlockTable()
    {
        $this->connection->exec('UNLOCK TABLES');
    }

    /**
     * @return RememberMe[]
     */
    public function findBySeries(string $encodedSeries): array
    {
        $qb = $this->createQueryBuilder('rm');

        $qb
            ->where('rm.series = :series')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('rm.expires'),
                    $qb->expr()->lte('rm.expires', 'NOW()')
                )
            )
            ->setParameter('series', $encodedSeries)
            ->orderBy($qb->expr()->isNull('rm.expires'), 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function deleteBySeries(string $encodedSeries): int
    {
        $table = $this->getClassMetadata()->getTableName();

        return $this->connection->delete($table, ['series' => $encodedSeries]);
    }

    public function deleteExpired(int $lastUsedLifetime, int $expiresLifetime): int
    {
        $table = $this->getClassMetadata()->getTableName();

        return $this->connection->executeUpdate(
            "DELETE FROM $table WHERE lastUsed<:lastUsed OR expires<:expires",
            [
                'lastUsed' => (new \DateTime())->sub(new \DateInterval('PT'.$lastUsedLifetime.'S')),
                'expires' => (new \DateTime())->sub(new \DateInterval('PT'.$expiresLifetime.'S')),
            ],
            [
                'lastUsed' => DoctrineType::DATETIME,
                'expires' => DoctrineType::DATETIME,
            ]
        );
    }

    public function persist(RememberMe ...$entities)
    {
        foreach ($entities as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
    }
}

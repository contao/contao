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

    public function lockTable(): void
    {
        $table = $this->getClassMetadata()->getTableName();

        $this->connection->exec("LOCK TABLES $table WRITE, $table AS t0_ WRITE");
    }

    public function unlockTable(): void
    {
        $this->connection->exec('UNLOCK TABLES');
    }

    /**
     * @return RememberMe[]
     */
    public function findBySeries(string $series): array
    {
        $qb = $this->createQueryBuilder('rm');
        $qb
            ->where('rm.series = :series')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('rm.expires'),
                    $qb->expr()->lte('rm.expires', ':now')
                )
            )
            ->setParameter('series', $series)
            ->setParameter('now', new \DateTime())
            ->orderBy('rm.expires', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function deleteSiblings(RememberMe $entity): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->delete($this->_entityName, 'rm')
            ->where('rm.series = :series')
            ->andWhere('rm.value != :value')
            ->setParameter('series', $entity->getSeries())
            ->setParameter('value', $entity->getValue())
        ;

        $qb->getQuery()->execute();
    }

    public function deleteBySeries(string $series): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->delete($this->_entityName, 'rm')
            ->where('rm.series = :series')
            ->setParameter('series', $series)
        ;

        $qb->getQuery()->execute();
    }

    public function deleteExpired(int $lastUsedLifetime, int $expiresLifetime): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->delete($this->_entityName, 'rm')
            ->where('rm.lastUsed < :lastUsed')
            ->orWhere('rm.expires < :expires')
            ->setParameter('lastUsed', (new \DateTime())->sub(new \DateInterval('PT'.$lastUsedLifetime.'S')))
            ->setParameter('expires', (new \DateTime())->sub(new \DateInterval('PT'.$expiresLifetime.'S')))
        ;

        $qb->getQuery()->execute();
    }

    public function persist(RememberMe ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
    }
}

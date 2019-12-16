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

use Contao\CoreBundle\Entity\Cron as CronEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class CronRepository extends ServiceEntityRepository
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronEntity::class);

        if (($connection = $registry->getConnection()) instanceof Connection) {
            $this->connection = $connection;
        }
    }

    public function lockTable(): void
    {
        $table = $this->getClassMetadata()->getTableName();

        $this->connection->exec("LOCK TABLES $table WRITE, $table AS t0 WRITE, $table AS t0_ WRITE");
    }

    public function unlockTable(): void
    {
        $this->connection->exec('UNLOCK TABLES');
    }

    public function persist(CronEntity ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
    }
}

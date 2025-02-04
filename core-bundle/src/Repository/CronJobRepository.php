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
     * @deprecated Deprecated since Contao 5.3, to be removed in Contao 6;
     *             use the Symfony Lock component instead.
     */
    public function lockTable(): void
    {
        trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" has been deprecated and will no longer work in Contao 6. Use the Symfony Lock compoenent instead.', __METHOD__);

        $table = $this->getClassMetadata()->getTableName();

        $this->connection->executeStatement("LOCK TABLES $table WRITE, $table AS t0 WRITE, $table AS t0_ WRITE");
    }

    /**
     * @deprecated Deprecated since Contao 5.3, to be removed in Contao 6;
     *             use the Symfony Lock component instead.
     */
    public function unlockTable(): void
    {
        trigger_deprecation('contao/core-bundle', '5.3', 'Using "%s()" has been deprecated and will no longer work in Contao 6. Use the Symfony Lock compoenent instead.', __METHOD__);

        $this->connection->executeStatement('UNLOCK TABLES');
    }
}

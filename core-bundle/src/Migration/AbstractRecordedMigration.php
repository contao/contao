<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

use Contao\CoreBundle\Entity\Migration as MigrationEntity;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

abstract class AbstractRecordedMigration extends AbstractMigration implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    public function shouldRun(): bool
    {
        return !$this->hasRun();
    }

    protected function hasRun(): ?bool
    {
        if (!$this->connection()->getSchemaManager()->tablesExist(['tl_migration'])) {
            return false;
        }

        return null !== $this->entityManager()->getRepository(MigrationEntity::class)->findOneBy(['name' => $this->getName()]);
    }

    protected function createResult(bool $successful, string $message = null): MigrationResult
    {
        if (!$this->hasRun()) {
            if (!$this->connection()->getSchemaManager()->tablesExist(['tl_migration'])) {
                $this->createMigrationTable();
            }

            $migrationEntity = new MigrationEntity($this->getName());
            $this->entityManager()->persist($migrationEntity);
            $this->entityManager()->flush();
        }

        return parent::createResult($successful, $message);
    }

    private function createMigrationTable(): void
    {
        $entityManager = $this->entityManager();
        $schemaTool = new SchemaTool($entityManager);

        // Get the SQL queries related only to tl_migration as Contao's DoctrineSchemaListener adds additional ones
        $createSchemaSql = array_filter($schemaTool->getCreateSchemaSql([$entityManager->getClassMetadata(MigrationEntity::class)]), function($sql): bool {
            return false !== strpos($sql, ' tl_migration ');
        });

        foreach ($createSchemaSql as $sql) {
            $this->connection()->executeQuery($sql);
        }
    }

    protected function connection(): Connection
    {
        return $this->container->get(__METHOD__);
    }

    private function entityManager(): EntityManagerInterface
    {
        return $this->container->get(__METHOD__);
    }
}

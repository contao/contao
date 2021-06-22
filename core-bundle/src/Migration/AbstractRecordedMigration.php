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
use Contao\CoreBundle\Repository\MigrationRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

abstract class AbstractRecordedMigration extends AbstractMigration implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    public function shouldRun(): bool
    {
        return false === $this->hasRun();
    }

    protected function hasRun(): ?bool
    {
        if (!$this->connection()->getSchemaManager()->tablesExist(['tl_migration'])) {
            return null;
        }

        return null !== $this->entityManager()->getRepository(MigrationEntity::class)->findOneBy(['name' => $this->getName()]);
    }

    protected function createResult(bool $successful, string $message = null): MigrationResult
    {
        if (false === $this->hasRun()) {
            $migrationEntity = new MigrationEntity($this->getName());
            $this->entityManager()->persist($migrationEntity);
            $this->entityManager()->flush();
        }

        return parent::createResult($successful, $message);
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

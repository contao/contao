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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

abstract class AbstractRecordedMigration extends AbstractMigration implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;

    public function shouldRun(): bool
    {
        return !$this->hasRun();
    }

    protected function hasRun(): bool
    {
        return null !== $this->entityManager()->getRepository(MigrationEntity::class)->findOneBy(['name' => $this->getName()]);
    }

    protected function createResult(bool $successful, string $message = null): MigrationResult
    {
        if (!$this->hasRun()) {
            $migrationEntity = new MigrationEntity($this->getName());
            $this->entityManager()->persist($migrationEntity);
            $this->entityManager()->flush();
        }

        return parent::createResult($successful, $message);
    }

    private function entityManager(): EntityManagerInterface
    {
        return $this->container->get(__METHOD__);
    }
}

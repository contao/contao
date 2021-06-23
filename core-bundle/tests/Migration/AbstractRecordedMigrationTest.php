<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration;

use Contao\CoreBundle\Entity\Migration as MigrationEntity;
use Contao\CoreBundle\Migration\AbstractRecordedMigration;
use Contao\CoreBundle\Tests\Fixtures\Migration\FooRecordedMigration;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Container\ContainerInterface;

class AbstractRecordedMigrationTest extends TestCase
{
    public function testDoesNotRunIfAlreadyExecuted(): void
    {
        $entity = new MigrationEntity(FooRecordedMigration::class);

        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => FooRecordedMigration::class])
            ->willReturn($entity)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;
        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(MigrationEntity::class)
            ->willReturn($repository)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with(AbstractRecordedMigration::class.'::entityManager')
            ->willReturn($entityManager)
        ;

        $migration = new FooRecordedMigration();
        $migration->setContainer($locator);

        $this->assertFalse($migration->shouldRun());
    }

    public function testRunsIfNotAlreadyExecuted(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => FooRecordedMigration::class])
            ->willReturn(null)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist')
        ;
        $entityManager
            ->expects($this->never())
            ->method('flush')
        ;
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(MigrationEntity::class)
            ->willReturn($repository)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with(AbstractRecordedMigration::class.'::entityManager')
            ->willReturn($entityManager)
        ;

        $migration = new FooRecordedMigration();
        $migration->setContainer($locator);

        $this->assertTrue($migration->shouldRun());
    }

    public function testCreatesMigrationEntity(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => FooRecordedMigration::class])
            ->willReturn(null)
        ;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
        ;
        $entityManager
            ->expects($this->once())
            ->method('flush')
        ;
        $entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(MigrationEntity::class)
            ->willReturn($repository)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->exactly(3))
            ->method('get')
            ->with(AbstractRecordedMigration::class.'::entityManager')
            ->willReturn($entityManager)
        ;

        $migration = new FooRecordedMigration();
        $migration->setContainer($locator);
        $migration->run();
    }
}

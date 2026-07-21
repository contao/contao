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

use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Contao\CoreBundle\Migration\CommandCompiler;
use Contao\CoreBundle\Migration\DatabaseMigrationRunner;
use Contao\CoreBundle\Migration\MigrationCollection;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationRunnerTest extends TestCase
{
    public function testRunsMigrationsBeforeAndAfterUpdatingTheSchema(): void
    {
        $calls = [];
        $migrations = $this->createMock(MigrationCollection::class);
        $migrations
            ->expects($this->exactly(2))
            ->method('runAll')
            ->willReturnCallback(
                static function () use (&$calls): void {
                    $calls[] = 'migrations';
                },
            )
        ;

        $compiler = $this->createMock(CommandCompiler::class);
        $compiler
            ->expects($this->once())
            ->method('runAll')
            ->with(false)
            ->willReturnCallback(
                static function () use (&$calls): void {
                    $calls[] = 'schema';
                },
            )
        ;

        $runner = new DatabaseMigrationRunner($compiler, $migrations, $this->createStub(BackupManager::class));

        $runner->runAll();

        $this->assertSame(['migrations', 'schema', 'migrations'], $calls);
    }

    public function testDelegatesPendingMigrationsAndSchemaCommands(): void
    {
        $migrations = $this->createStub(MigrationCollection::class);
        $migrations
            ->method('getPendingNames')
            ->willReturn(['Migration 1'])
        ;

        $compiler = $this->createMock(CommandCompiler::class);
        $compiler
            ->expects($this->once())
            ->method('compileCommands')
            ->with(false)
            ->willReturn(['ALTER TABLE tl_foo ADD bar INT'])
        ;

        $runner = new DatabaseMigrationRunner($compiler, $migrations, $this->createStub(BackupManager::class));

        $this->assertSame(['Migration 1'], iterator_to_array($runner->getPendingMigrationNames()));
        $this->assertTrue($runner->hasWorkToDo());
    }

    public function testDelegatesSqlExecutionToCompiler(): void
    {
        $compiler = $this->createMock(CommandCompiler::class);
        $compiler
            ->expects($this->once())
            ->method('executeSqlCommand')
            ->with('ALTER TABLE tl_foo ADD bar INT')
        ;

        $runner = new DatabaseMigrationRunner(
            $compiler,
            $this->createStub(MigrationCollection::class),
            $this->createStub(BackupManager::class),
        );

        $runner->executeSqlCommand('ALTER TABLE tl_foo ADD bar INT');
    }

    public function testUsesTheProvidedBackupConfiguration(): void
    {
        $config = new CreateConfig(new Backup('valid_backup__20211101141254.sql'));
        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->expects($this->once())
            ->method('create')
            ->with($config)
        ;

        $runner = new DatabaseMigrationRunner(
            $this->createStub(CommandCompiler::class),
            $this->createStub(MigrationCollection::class),
            $backupManager,
        );

        $this->assertSame($config->getBackup()->toArray(), $runner->createBackup($config));
    }
}

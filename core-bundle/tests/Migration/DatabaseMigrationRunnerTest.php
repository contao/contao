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
use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Contao\CoreBundle\Doctrine\Schema\MysqlInnodbRowSizeCalculator;
use Contao\CoreBundle\Migration\CommandCompiler;
use Contao\CoreBundle\Migration\DatabaseMigrationDecision;
use Contao\CoreBundle\Migration\DatabaseMigrationEvent;
use Contao\CoreBundle\Migration\DatabaseMigrationEventType;
use Contao\CoreBundle\Migration\DatabaseMigrationHashMismatchException;
use Contao\CoreBundle\Migration\DatabaseMigrationObserverInterface;
use Contao\CoreBundle\Migration\DatabaseMigrationRunner;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationConfiguration;
use Contao\CoreBundle\Migration\MigrationExecutionMode;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Migration\SchemaUpdateMode;
use Contao\CoreBundle\Migration\WarningMode;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\AbstractException;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver as PdoDriver;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

class DatabaseMigrationRunnerTest extends TestCase
{
    public function testNoPendingWorkSkipsBackup(): void
    {
        $connection = $this->createConnection();
        $commandCompiler = $this->createMock(CommandCompiler::class);
        $commandCompiler
            ->expects($this->atLeastOnce())
            ->method('compileCommands')
            ->willReturn([])
        ;

        $commandCompiler
            ->method('compileTargetSchema')
            ->willReturn(new Schema())
        ;
        $migrations = $this->createMock(MigrationCollection::class);
        $migrations
            ->expects($this->once())
            ->method('hasPending')
            ->willReturn(false)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturn([])
        ;

        $migrations
            ->method('run')
            ->willReturn([])
        ;
        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->method('createCreateConfig')
            ->willReturn(new CreateConfig(new Backup('valid_backup_filename__20211101141254.sql')))
        ;

        $backupManager
            ->expects($this->never())
            ->method('create')
        ;
        $observer = $this->createObserver();
        $runner = $this->createRunner($commandCompiler, $connection, $migrations, $backupManager, $this->createRowSizeCalculator());

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withCreateBackup(true),
            $observer,
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testBackupFailureAborts(): void
    {
        $connection = $this->createConnection();
        $runner = $this->createRunner(
            $this->createCommandCompiler([], []),
            $connection,
            $this->createMigrationCollection(['Migration 1']),
            $this->createBackupManager(true),
            $this->createRowSizeCalculator(),
        );
        $observer = $this->createObserver();

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withCreateBackup(true),
            $observer,
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(DatabaseMigrationEventType::BackupResult, $observer->events[0]->getType());
        $this->assertSame(DatabaseMigrationEventType::Error, $observer->events[1]->getType());
    }

    public function testDryRunDoesNotExecuteMigrationsOrSql(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('executeQuery')
        ;

        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(static fn (string $query): int|string|false => match ($query) {
                'SELECT @@sql_mode' => 'TRADITIONAL',
                'SELECT @@version' => '8.0.0',
                'SELECT @@innodb_strict_mode' => 1,
                default => false,
            })
        ;
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn('8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $commandCompiler = $this->createCommandCompiler(
            ['ALTER TABLE tl_test ADD foo INT NULL'],
            ['ALTER TABLE tl_test ADD foo INT NULL'],
        );

        $migrations = $this->createMock(MigrationCollection::class);
        $migrations
            ->method('hasPending')
            ->willReturn(true)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturn(['Migration 1'])
        ;

        $migrations
            ->expects($this->never())
            ->method('run')
        ;

        $runner = $this->createRunner($commandCompiler, $connection, $migrations, $this->createBackupManager(), $this->createRowSizeCalculator());

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withDryRun(true),
            $this->createObserver(),
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testMigrationsOnlySkipsSchemaExecution(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('executeQuery')
        ;

        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(static fn (string $query): int|string|false => match ($query) {
                'SELECT @@sql_mode' => 'TRADITIONAL',
                'SELECT @@version' => '8.0.0',
                'SELECT @@innodb_strict_mode' => 1,
                default => false,
            })
        ;
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn('8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $commandCompiler = $this->createMock(CommandCompiler::class);
        $commandCompiler
            ->expects($this->never())
            ->method('compileCommands')
        ;

        $commandCompiler
            ->method('compileTargetSchema')
            ->willReturn(new Schema())
        ;

        $migrations = $this->createMock(MigrationCollection::class);
        $migrations
            ->method('hasPending')
            ->willReturn(true)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturnOnConsecutiveCalls(['Migration 1'], [])
        ;

        $migrations
            ->expects($this->once())
            ->method('run')
            ->with(['Migration 1'])
            ->willReturn([new MigrationResult(true, 'Result 1')])
        ;

        $runner = $this->createRunner($commandCompiler, $connection, $migrations, $this->createBackupManager(), $this->createRowSizeCalculator());

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withMigrationsOnly(true)
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::Skip),
            $this->createObserver(),
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testSchemaOnlyUsesWithoutDeletesByDefault(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('ALTER TABLE tl_test ADD foo INT NULL')
            ->willReturn($this->createStub(Result::class))
        ;

        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(static fn (string $query): int|string|false => match ($query) {
                'SELECT @@sql_mode' => 'TRADITIONAL',
                'SELECT @@version' => '8.0.0',
                'SELECT @@innodb_strict_mode' => 1,
                default => false,
            })
        ;
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn('8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $commands = ['ALTER TABLE tl_test DROP bar', 'ALTER TABLE tl_test ADD foo INT NULL'];
        $commandCompiler = $this->createStub(CommandCompiler::class);
        $commandCompiler
            ->method('compileCommands')
            ->willReturnCallback(static fn (bool $skipDropStatements = false): array => $skipDropStatements ? ['ALTER TABLE tl_test ADD foo INT NULL'] : $commands)
        ;

        $schema = $this->createSchemaWithBigTable();
        $commandCompiler
            ->method('compileTargetSchema')
            ->willReturn($schema)
        ;

        $runner = $this->createRunner($commandCompiler, $connection, $this->createMigrationCollection([]), $this->createBackupManager(), $this->createRowSizeCalculator());
        $observer = $this->createObserver();

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withSchemaOnly(true)
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::WithoutDeletes),
            $observer,
        );

        $this->assertTrue($result->isSuccessful());

        $schemaPendingEvents = array_values(array_filter(
            $observer->events,
            static fn (DatabaseMigrationEvent $event): bool => DatabaseMigrationEventType::SchemaPending === $event->getType(),
        ));

        $this->assertNotEmpty($schemaPendingEvents);
        $this->assertSame($commands, $schemaPendingEvents[0]->getPayload()['commands']);

        $sortedCommands = $commands;
        sort($sortedCommands);

        $this->assertSame(hash('sha256', json_encode($sortedCommands, JSON_THROW_ON_ERROR)), $schemaPendingEvents[0]->getPayload()['hash']);
    }

    public function testSchemaOnlyUsesDeletesWhenAllowed(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->willReturn($this->createStub(Result::class))
        ;

        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(static fn (string $query): int|string|false => match ($query) {
                'SELECT @@sql_mode' => 'TRADITIONAL',
                'SELECT @@version' => '8.0.0',
                'SELECT @@innodb_strict_mode' => 1,
                default => false,
            })
        ;
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn('8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $commandCompiler = $this->createMock(CommandCompiler::class);
        $commandCompiler
            ->expects($this->atLeastOnce())
            ->method('compileCommands')
            ->with()
            ->willReturn(['ALTER TABLE tl_test ADD foo INT NULL', 'ALTER TABLE tl_test DROP bar'])
        ;

        $schema = $this->createSchemaWithBigTable();
        $commandCompiler
            ->method('compileTargetSchema')
            ->willReturn($schema)
        ;

        $runner = $this->createRunner($commandCompiler, $connection, $this->createMigrationCollection([]), $this->createBackupManager(), $this->createRowSizeCalculator());

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withSchemaOnly(true)
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::WithDeletes),
            $this->createObserver(),
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testAskModeSchemaDecisionWithDeletesExecutesDropCommands(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->willReturn($this->createStub(Result::class))
        ;

        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(static fn (string $query): int|string|false => match ($query) {
                'SELECT @@sql_mode' => 'TRADITIONAL',
                'SELECT @@version' => '8.0.0',
                'SELECT @@innodb_strict_mode' => 1,
                default => false,
            })
        ;
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn('8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $commands = ['ALTER TABLE tl_test DROP bar', 'ALTER TABLE tl_test ADD foo INT NULL'];
        $commandCompiler = $this->createStub(CommandCompiler::class);
        $commandCompiler
            ->method('compileCommands')
            ->willReturnCallback(static fn (bool $skipDropStatements = false): array => $skipDropStatements ? ['ALTER TABLE tl_test ADD foo INT NULL'] : $commands)
        ;

        $schema = $this->createSchemaWithBigTable();
        $commandCompiler
            ->method('compileTargetSchema')
            ->willReturn($schema)
        ;

        $runner = $this->createRunner($commandCompiler, $connection, $this->createMigrationCollection([]), $this->createBackupManager(), $this->createRowSizeCalculator());
        $observer = $this->createObserver(static fn (DatabaseMigrationEvent $event): DatabaseMigrationDecision|null => DatabaseMigrationEventType::SchemaPending === $event->getType() ? DatabaseMigrationDecision::WithDeletes : null);

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::Ask),
            $observer,
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testExecutesPendingMigrationsAndSchemaDiff(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->atLeastOnce())
            ->method('executeQuery')
            ->willReturn($this->createStub(Result::class))
        ;

        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(static fn (string $query): int|string|false => match ($query) {
                'SELECT @@sql_mode' => 'TRADITIONAL',
                'SELECT @@version' => '8.0.0',
                'SELECT @@innodb_strict_mode' => 1,
                default => false,
            })
        ;
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn('8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;
        $commandCompiler = $this->createMock(CommandCompiler::class);
        $compileCalls = 0;
        $commandCompiler
            ->expects($this->atLeastOnce())
            ->method('compileCommands')
            ->willReturnCallback(
                static function (bool $skipDropStatements = false) use (&$compileCalls): array {
                    return match (++$compileCalls) {
                        1 => ['ALTER TABLE tl_test DROP bar', 'ALTER TABLE tl_test ADD foo INT NULL'],
                        2 => ['ALTER TABLE tl_test ADD foo INT NULL'],
                        default => [],
                    };
                },
            )
        ;

        $commandCompiler
            ->method('compileTargetSchema')
            ->willReturn($this->createSchemaWithBigTable())
        ;

        $migrations = $this->createMock(MigrationCollection::class);
        $migrations
            ->method('hasPending')
            ->willReturn(true)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturnOnConsecutiveCalls(['Migration 1', 'Migration 2'], [], [])
        ;

        $migrations
            ->expects($this->once())
            ->method('run')
            ->with(['Migration 1', 'Migration 2'])
            ->willReturn([
                new MigrationResult(true, 'Result 1'),
                new MigrationResult(true, 'Result 2'),
            ])
        ;

        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->method('createCreateConfig')
            ->willReturn(new CreateConfig(new Backup('valid_backup_filename__20211101141254.sql')))
        ;

        $backupManager
            ->expects($this->once())
            ->method('create')
        ;

        $runner = $this->createRunner($commandCompiler, $connection, $migrations, $backupManager, $this->createRowSizeCalculator());

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withCreateBackup(true),
            $this->createObserver(),
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testDoesNotAbortIfMigrationFails(): void
    {
        $migrations = $this->createMock(MigrationCollection::class);
        $migrations
            ->method('hasPending')
            ->willReturn(true)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturnOnConsecutiveCalls(['Migration 1', 'Migration 2'], [], [])
        ;

        $migrations
            ->expects($this->once())
            ->method('run')
            ->with(['Migration 1', 'Migration 2'])
            ->willReturn([
                new MigrationResult(false, 'Result 1'),
                new MigrationResult(true, 'Result 2'),
            ])
        ;

        $runner = $this->createRunner(
            $this->createCommandCompiler([], []),
            $this->createConnection(),
            $migrations,
            $this->createBackupManager(),
            $this->createRowSizeCalculator(),
        );

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::WithoutDeletes),
            $this->createObserver(),
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testHashMismatchThrows(): void
    {
        $runner = $this->createRunner(
            $this->createCommandCompiler([], []),
            $this->createConnection(),
            $this->createMigrationCollection(['Migration 1'], [new MigrationResult(true, 'Result 1')]),
            $this->createBackupManager(),
            $this->createRowSizeCalculator(),
        );

        $this->expectException(DatabaseMigrationHashMismatchException::class);
        $this->expectExceptionMessage('Specified hash');

        $runner->run(
            MigrationConfiguration::create()
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::WithoutDeletes)
                ->withHash('wrong-hash'),
            $this->createObserver(),
        );
    }

    public function testHashMakesSchemaStageReadOnlyAfterMigrations(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('executeQuery')
        ;

        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(static fn (string $query): int|string|false => match ($query) {
                'SELECT @@sql_mode' => 'TRADITIONAL',
                'SELECT @@version' => '8.0.0',
                'SELECT @@innodb_strict_mode' => 1,
                default => false,
            })
        ;
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn('8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $commandCompiler = $this->createCommandCompiler(['ALTER TABLE tl_test ADD foo INT NULL'], ['ALTER TABLE tl_test ADD foo INT NULL']);
        $migrations = $this->createMock(MigrationCollection::class);
        $migrations
            ->method('hasPending')
            ->willReturn(true)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturnOnConsecutiveCalls(['Migration 1'], [])
        ;

        $migrations
            ->expects($this->once())
            ->method('run')
            ->with(['Migration 1'])
            ->willReturn([new MigrationResult(true, 'Result 1')])
        ;

        $runner = $this->createRunner($commandCompiler, $connection, $migrations, $this->createBackupManager(), $this->createRowSizeCalculator());

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::WithoutDeletes)
                ->withHash(hash('sha256', json_encode(['Migration 1'], JSON_THROW_ON_ERROR))),
            $this->createObserver(),
        );

        $this->assertTrue($result->isSuccessful());
    }

    public function testAskModeWithoutObserverFailsClearly(): void
    {
        $runner = $this->createRunner(
            $this->createCommandCompiler([], []),
            $this->createConnection(),
            $this->createMigrationCollection([]),
            $this->createBackupManager(),
            $this->createRowSizeCalculator(),
        );

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withWarningMode(WarningMode::Ask)
                ->withMigrationExecutionMode(MigrationExecutionMode::Ask)
                ->withSchemaUpdateMode(SchemaUpdateMode::Ask),
            null,
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('An observer is required when ask mode is enabled.', $result->getMessage());
    }

    public function testAskModeCanSkipMigrations(): void
    {
        $migrations = $this->createMock(MigrationCollection::class);
        $migrations
            ->method('hasPending')
            ->willReturn(true)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturn(['Migration 1'])
        ;

        $migrations
            ->expects($this->never())
            ->method('run')
        ;

        $runner = $this->createRunner(
            $this->createCommandCompiler([], []),
            $this->createConnection(),
            $migrations,
            $this->createBackupManager(),
            $this->createRowSizeCalculator(),
        );

        $observer = $this->createObserver(static fn (DatabaseMigrationEvent $event): DatabaseMigrationDecision|null => DatabaseMigrationEventType::MigrationPending === $event->getType() ? DatabaseMigrationDecision::Skip : null);

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Ask)
                ->withSchemaUpdateMode(SchemaUpdateMode::Skip),
            $observer,
        );

        $this->assertFalse($result->isSuccessful());
    }

    #[DataProvider('provideConfigurationErrors')]
    public function testOutputsConfigurationErrors(array $configuration, string $expectedMessage): void
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchOne')
            ->willReturnCallback(
                static fn (string $query) => match ($query) {
                    'SELECT @@version' => $configuration['version'] ?? '8.0.0',
                    'SELECT @@sql_mode' => 'TRADITIONAL',
                    'SELECT @@innodb_strict_mode' => 1,
                    default => false,
                },
            )
        ;
        $connection
            ->method('getParams')
            ->willReturn(['defaultTableOptions' => $configuration['defaultTableOptions'] ?? []])
        ;

        $connection
            ->method('fetchAssociative')
            ->willReturnCallback(
                static fn (string $query): array|false => match ($query) {
                    \sprintf("SHOW COLLATION LIKE '%s'", $configuration['defaultTableOptions']['collate'] ?? '') => $configuration['collation'] ?? false,
                    "SHOW VARIABLES LIKE 'innodb_large_prefix'" => $configuration['innodb_large_prefix'] ?? false,
                    "SHOW VARIABLES LIKE 'innodb_file_per_table'" => $configuration['innodb_file_per_table'] ?? false,
                    "SHOW VARIABLES LIKE 'innodb_file_format'" => $configuration['innodb_file_format'] ?? false,
                    default => false,
                },
            )
        ;
        $connection
            ->method('fetchAllAssociative')
            ->willReturn($configuration['engines'] ?? [])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn($configuration['version'] ?? '8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $runner = $this->createRunner(
            $this->createCommandCompiler([], []),
            $connection,
            $this->createMigrationCollection([]),
            $this->createBackupManager(),
            $this->createRowSizeCalculator(),
        );

        $result = $runner->run(
            MigrationConfiguration::create(),
            $this->createObserver(),
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertStringContainsString($expectedMessage, (string) $result->getMessage());
    }

    #[DataProvider('provideWrongServerVersion')]
    public function testAbortsOnWrongServerVersion(string $serverVersion, string $configuredVersion): void
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('getServerVersion')
            ->willReturn($serverVersion)
        ;

        $connection
            ->method('getParams')
            ->willReturn(['serverVersion' => $configuredVersion])
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new SQLitePlatform())
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('fetchOne')
            ->willReturnMap([
                ['SELECT @@version', $serverVersion],
                ['SELECT @@sql_mode', 'TRADITIONAL'],
                ['SELECT @@innodb_strict_mode', 1],
            ])
        ;

        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $runner = $this->createRunner(
            $this->createCommandCompiler([], []),
            $connection,
            $this->createMigrationCollection([]),
            $this->createBackupManager(),
            $this->createRowSizeCalculator(),
        );

        $observer = $this->createObserver();
        $result = $runner->run(
            MigrationConfiguration::create(),
            $observer,
        );

        $this->assertFalse($result->isSuccessful());
        $this->assertSame(DatabaseMigrationEventType::Problem, $observer->events[0]->getType());
    }

    #[DataProvider('provideWarningModes')]
    public function testWarningsFollowConfiguredPolicy(WarningMode $warningMode, DatabaseMigrationDecision|null $decision, bool $expectedSuccess): void
    {
        $connection = $this->createConnection('');
        $schema = $this->createSchemaWithBigTable();
        $commandCompiler = $this->createCommandCompiler([], [], $schema);
        $runner = $this->createRunner($commandCompiler, $connection, $this->createMigrationCollection([]), $this->createBackupManager(), $this->createRowSizeCalculator());
        $observer = $this->createObserver(static fn (DatabaseMigrationEvent $event): DatabaseMigrationDecision|null => DatabaseMigrationEventType::Warning === $event->getType() ? $decision : null);

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withWarningMode($warningMode)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::WithoutDeletes),
            $observer,
        );

        $this->assertSame($expectedSuccess, $result->isSuccessful());
    }

    public function testRowSizeSqlRetryBehavior(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnMap([
                ['SELECT @@sql_mode', 'TRADITIONAL'],
                ['SELECT @@version', '8.0.0'],
                ['SELECT @@innodb_strict_mode', 1],
            ])
        ;

        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn('8.0.0')
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $callCount = 0;
        $result = $this->createStub(Result::class);
        $connection
            ->expects($this->exactly(5))
            ->method('executeQuery')
            ->willReturnCallback(
                static function (string $query) use (&$callCount, $result): Result {
                    ++$callCount;

                    if (1 === $callCount) {
                        $driverException = new class('Row size too large', '42000', 1118) extends AbstractException {
                        };

                        throw new DriverException($driverException, null);
                    }

                    return $result;
                },
            )
        ;

        $commandCompiler = $this->createCommandCompiler(['ALTER TABLE tl_test ADD foo INT NULL'], ['ALTER TABLE tl_test ADD foo INT NULL']);
        $runner = $this->createRunner($commandCompiler, $connection, $this->createMigrationCollection([]), $this->createBackupManager(), $this->createRowSizeCalculator());

        $result = $runner->run(
            MigrationConfiguration::create()
                ->withSchemaOnly(true)
                ->withWarningMode(WarningMode::Continue)
                ->withMigrationExecutionMode(MigrationExecutionMode::Execute)
                ->withSchemaUpdateMode(SchemaUpdateMode::WithDeletes),
            $this->createObserver(),
        );

        $this->assertTrue($result->isSuccessful());
    }

    public static function provideWarningModes(): iterable
    {
        yield 'continue' => [WarningMode::Continue, null, true];
        yield 'abort' => [WarningMode::Abort, null, false];
        yield 'ask continue' => [WarningMode::Ask, DatabaseMigrationDecision::Continue, true];
        yield 'ask abort' => [WarningMode::Ask, DatabaseMigrationDecision::Abort, false];
    }

    public static function provideConfigurationErrors(): iterable
    {
        yield 'database version too old' => [
            ['version' => '5.0.10'],
            'Your database version is not supported!',
        ];

        yield 'unsupported collation' => [
            ['defaultTableOptions' => ['collate' => 'foo']],
            'The configured collation is not supported!',
        ];

        yield 'unsupported engine' => [
            [
                'defaultTableOptions' => ['engine' => 'MyISAM'],
                'engines' => [
                    ['Engine' => 'MEMORY', 'Comment' => 'Hash based, stored in memory, useful for temporary tables'],
                    ['Engine' => 'InnoDB', 'Comment' => 'Supports transactions, row-level locking, foreign keys and encryption for tables'],
                ],
            ],
            'The configured database engine is not supported!',
        ];
    }

    public static function provideWrongServerVersion(): iterable
    {
        yield 'mismatched server version' => ['8.0.29', '5.7.39'];
    }

    private function createRunner(CommandCompiler $commandCompiler, Connection $connection, MigrationCollection $migrations, BackupManager $backupManager, MysqlInnodbRowSizeCalculator $rowSizeCalculator): DatabaseMigrationRunner
    {
        return new DatabaseMigrationRunner($commandCompiler, $connection, $migrations, $backupManager, $rowSizeCalculator);
    }

    private function createObserver(\Closure|null $callback = null): object
    {
        return new class($callback) implements DatabaseMigrationObserverInterface {
            /**
             * @var list<DatabaseMigrationEvent>
             */
            public array $events = [];

            public function __construct(private readonly \Closure|null $callback)
            {
            }

            public function notify(DatabaseMigrationEvent $event): DatabaseMigrationDecision|null
            {
                $this->events[] = $event;

                return $this->callback ? ($this->callback)($event) : null;
            }
        };
    }

    private function createConnection(string $sqlMode = 'TRADITIONAL', string $serverVersion = '8.0.0'): Connection
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(
                static fn (string $query): int|string|false => match ($query) {
                    'SELECT @@sql_mode' => $sqlMode,
                    'SELECT @@version' => $serverVersion,
                    'SELECT @@innodb_strict_mode' => 1,
                    default => false,
                },
            )
        ;
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $connection
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $connection
            ->method('getServerVersion')
            ->willReturn($serverVersion)
        ;

        $connection
            ->method('getDriver')
            ->willReturn(new PdoDriver())
        ;

        $connection
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform())
        ;

        $connection
            ->method('executeQuery')
            ->willReturn($this->createStub(Result::class))
        ;

        return $connection;
    }

    private function createCommandCompiler(array $commandsWithDeletes, array $commandsWithoutDeletes, Schema|null $schema = null): CommandCompiler
    {
        $commandCompiler = $this->createStub(CommandCompiler::class);
        $commandCompiler
            ->method('compileTargetSchema')
            ->willReturn($schema ?? new Schema())
        ;

        $commandCompiler
            ->method('compileCommands')
            ->willReturnCallback(
                static fn (bool $skipDropStatements = false): array => $skipDropStatements ? $commandsWithoutDeletes : $commandsWithDeletes,
            )
        ;

        return $commandCompiler;
    }

    private function createMigrationCollection(array $pendingNames, array $migrationResults = []): MigrationCollection
    {
        $migrations = $this->createStub(MigrationCollection::class);
        $migrations
            ->method('hasPending')
            ->willReturn([] !== $pendingNames)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturn($pendingNames)
        ;

        $migrations
            ->method('run')
            ->willReturn($migrationResults)
        ;

        return $migrations;
    }

    private function createBackupManager(bool $fail = false): BackupManager
    {
        $backupManager = $this->createStub(BackupManager::class);
        $backupManager
            ->method('createCreateConfig')
            ->willReturn(new CreateConfig(new Backup('valid_backup_filename__20211101141254.sql')))
        ;
        $backupManager
            ->method('create')
            ->willReturnCallback(
                static function () use ($fail): void {
                    if ($fail) {
                        throw new BackupManagerException('Something went wrong.');
                    }
                },
            )
        ;

        return $backupManager;
    }

    private function createRowSizeCalculator(): MysqlInnodbRowSizeCalculator
    {
        $calculator = $this->createStub(MysqlInnodbRowSizeCalculator::class);
        $calculator
            ->method('getMysqlRowSize')
            ->willReturn(1000)
        ;

        $calculator
            ->method('getMysqlRowSizeLimit')
            ->willReturn(100)
        ;

        $calculator
            ->method('getInnodbRowSize')
            ->willReturn(1000)
        ;

        $calculator
            ->method('getInnodbRowSizeLimit')
            ->willReturn(100)
        ;

        return $calculator;
    }

    private function createSchemaWithBigTable(): Schema
    {
        $schema = new Schema();
        $table = $schema->createTable('tl_test');
        $table->addOption('engine', 'InnoDB');

        return $schema;
    }
}

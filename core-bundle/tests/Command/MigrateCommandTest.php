<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\MigrateCommand;
use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Contao\CoreBundle\Doctrine\Schema\MysqlInnodbRowSizeCalculator;
use Contao\CoreBundle\Migration\CommandCompiler;
use Contao\CoreBundle\Migration\DatabaseMigrationHashMismatchException;
use Contao\CoreBundle\Migration\DatabaseMigrationResult;
use Contao\CoreBundle\Migration\DatabaseMigrationRunner;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationConfiguration;
use Contao\CoreBundle\Migration\MigrationExecutionMode;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Migration\SchemaUpdateMode;
use Contao\CoreBundle\Migration\UnexpectedPendingMigrationException;
use Contao\CoreBundle\Migration\WarningMode;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver as PdoDriver;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandTest extends TestCase
{
    public function testMapsInteractiveTxtStateToAskModes(): void
    {
        $captured = null;
        $command = $this->createStubCommand(
            static function (MigrationConfiguration $configuration) use (&$captured): DatabaseMigrationResult {
                $captured = $configuration;

                return DatabaseMigrationResult::success();
            },
        );

        $tester = new CommandTester($command);
        $code = $tester->execute([], ['interactive' => true]);

        $this->assertSame(0, $code);
        $this->assertInstanceOf(MigrationConfiguration::class, $captured);
        $this->assertSame(WarningMode::Ask, $captured->getWarningMode());
        $this->assertSame(MigrationExecutionMode::Ask, $captured->getMigrationExecutionMode());
        $this->assertSame(SchemaUpdateMode::Ask, $captured->getSchemaUpdateMode());
        $this->assertFalse($captured->shouldSkipDropStatementsForBackup());
        $this->assertTrue($captured->shouldSkipDropStatementsForSchemaWarnings());
        $this->assertTrue($captured->shouldCreateBackup());
    }

    public function testMapsNonInteractiveStateToDeterministicModes(): void
    {
        $captured = null;
        $command = $this->createStubCommand(
            static function (MigrationConfiguration $configuration) use (&$captured): DatabaseMigrationResult {
                $captured = $configuration;

                return DatabaseMigrationResult::success();
            },
        );

        $tester = new CommandTester($command);
        $code = $tester->execute(['--no-backup' => true, '--with-deletes' => true], ['interactive' => false]);

        $this->assertSame(0, $code);
        $this->assertInstanceOf(MigrationConfiguration::class, $captured);
        $this->assertSame(WarningMode::Continue, $captured->getWarningMode());
        $this->assertSame(MigrationExecutionMode::Execute, $captured->getMigrationExecutionMode());
        $this->assertSame(SchemaUpdateMode::WithDeletes, $captured->getSchemaUpdateMode());
        $this->assertFalse($captured->shouldSkipDropStatementsForBackup());
        $this->assertFalse($captured->shouldSkipDropStatementsForSchemaWarnings());
        $this->assertFalse($captured->shouldCreateBackup());
    }

    #[DataProvider('provideInvalidOptionCombinations')]
    public function testRejectsInvalidOptionCombinations(array $arguments, string $expectedMessage): void
    {
        $command = $this->createStubCommand();
        $tester = new CommandTester($command);

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage($expectedMessage);

        $tester->execute($arguments, ['interactive' => false]);
    }

    public function testRejectsNdjsonInInteractiveNonDryRunMode(): void
    {
        $command = $this->createStubCommand();
        $tester = new CommandTester($command);

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('Use --no-interaction or --dry-run together with --format=ndjson');

        $tester->execute(['--format' => 'ndjson'], ['interactive' => true]);
    }

    public function testMapsExitCodeFromRunnerResult(): void
    {
        $successCommand = $this->createStubCommand(static fn (): DatabaseMigrationResult => DatabaseMigrationResult::success());
        $successTester = new CommandTester($successCommand);

        $this->assertSame(0, $successTester->execute([], ['interactive' => false]));

        $failureCommand = $this->createStubCommand(static fn (): DatabaseMigrationResult => DatabaseMigrationResult::failure());
        $failureTester = new CommandTester($failureCommand);

        $this->assertSame(1, $failureTester->execute([], ['interactive' => false]));
    }

    public function testPrintsFinalSuccessMessageOnlyForNormalTxtAndDryRunRuns(): void
    {
        $command = $this->createStubCommand(static fn (): DatabaseMigrationResult => DatabaseMigrationResult::success());

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute([], ['interactive' => false]));
        $this->assertStringContainsString('All migrations completed.', $tester->getDisplay());

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute(['--dry-run' => true], ['interactive' => false]));
        $this->assertStringContainsString('All migrations completed.', $tester->getDisplay());

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute(['--migrations-only' => true], ['interactive' => false]));
        $this->assertStringNotContainsString('All migrations completed.', $tester->getDisplay());

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute(['--schema-only' => true], ['interactive' => false]));
        $this->assertStringNotContainsString('All migrations completed.', $tester->getDisplay());

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute(['--format' => 'ndjson'], ['interactive' => false]));
        $this->assertStringNotContainsString('All migrations completed.', $tester->getDisplay());
    }

    public function testConvertsHashMismatchToInvalidOptionException(): void
    {
        $command = $this->createStubCommand(
            static fn (): DatabaseMigrationResult => throw new DatabaseMigrationHashMismatchException('Specified hash "x" does not match the actual hash "y"'),
        );
        $tester = new CommandTester($command);

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('Specified hash "x" does not match the actual hash "y"');

        $tester->execute(['--hash' => 'x'], ['interactive' => false]);
    }

    public function testInteractiveTxtWarningPromptAnswerNoAbortsBeforeSchemaExecution(): void
    {
        $connection = $this->createMock(Connection::class);
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
            ->method('getParams')
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

        $connection
            ->expects($this->never())
            ->method('executeQuery')
        ;

        $command = $this->createCommand(
            $this->createRunner(
                $this->createCommandCompiler(
                    ['ALTER TABLE tl_test ADD foo INT NULL'],
                    ['ALTER TABLE tl_test ADD foo INT NULL'],
                    $this->createSchemaWithBigTable(),
                ),
                $connection,
                $this->createMigrationCollection([]),
                $this->createBackupManager(),
                $this->createRowSizeCalculator(),
            ),
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['no']);

        $code = $tester->execute(['--no-backup' => true], ['interactive' => true]);
        $display = $tester->getDisplay();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Continue regardless of the warnings?', $display);
        $this->assertStringContainsString('The row size of table tl_test is too large', $display);
        $this->assertStringNotContainsString('Execute database migrations', $display);
    }

    public function testNonInteractiveNdjsonWarningsEmitWarningEventsButNoSummary(): void
    {
        $command = $this->createCommand(
            $this->createRunner(
                $this->createCommandCompiler(
                    ['ALTER TABLE tl_test ADD foo INT NULL'],
                    ['ALTER TABLE tl_test ADD foo INT NULL'],
                    $this->createSchemaWithBigTable(),
                ),
                $this->createConnection(),
                $this->createMigrationCollection([]),
                $this->createBackupManager(),
                $this->createRowSizeCalculator(),
            ),
        );

        $tester = new CommandTester($command);
        $code = $tester->execute(['--format' => 'ndjson', '--no-backup' => true], ['interactive' => false]);

        $this->assertSame(0, $code);
        $events = $this->jsonArrayFromNdjson($tester->getDisplay());
        $this->assertSame(1, array_reduce($events, static fn (int $count, array $event): int => $count + ('warning' === $event['type'] ? 1 : 0), 0));
        $this->assertNotContains('warning-summary', array_column($events, 'type'));
    }

    public function testNdjsonSuccessfulSchemaDiffPreservesOldEventSequence(): void
    {
        $connection = $this->createMock(Connection::class);
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

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('ALTER TABLE tl_test ADD foo INT NULL')
            ->willReturn($this->createStub(Result::class))
        ;

        $command = $this->createCommand(
            $this->createRunner(
                $this->createCommandCompiler(['ALTER TABLE tl_test ADD foo INT NULL'], ['ALTER TABLE tl_test ADD foo INT NULL']),
                $connection,
                $this->createMigrationCollection([]),
                $this->createBackupManager(),
                $this->createRowSizeCalculator(),
            ),
        );

        $tester = new CommandTester($command);
        $code = $tester->execute(['--format' => 'ndjson', '--no-backup' => true], ['interactive' => false]);

        $this->assertSame(0, $code);
        $events = $this->jsonArrayFromNdjson($tester->getDisplay());
        $this->assertSame(['migration-pending', 'schema-pending', 'schema-execute', 'schema-result', 'schema-pending', 'migration-pending'], array_column($events, 'type'));
        $this->assertSame([], $events[0]['names']);
        $this->assertArrayHasKey('hash', $events[0]);
        $this->assertSame(['ALTER TABLE tl_test ADD foo INT NULL'], $events[1]['commands']);
        $this->assertArrayHasKey('hash', $events[1]);
        $this->assertSame('ALTER TABLE tl_test ADD foo INT NULL', $events[2]['command']);
        $this->assertSame('ALTER TABLE tl_test ADD foo INT NULL', $events[3]['command']);
        $this->assertTrue($events[3]['isSuccessful']);
        $this->assertSame(['ALTER TABLE tl_test ADD foo INT NULL'], $events[4]['commands']);
        $this->assertSame([], $events[5]['names']);
    }

    public function testTxtAndNdjsonConfigurationErrorsHaveStableShape(): void
    {
        $txtCommand = $this->createCommand(
            $this->createRunner(
                $this->createCommandCompiler([], []),
                $this->createConnection('TRADITIONAL', '5.0.10'),
                $this->createMigrationCollection([]),
                $this->createBackupManager(),
                $this->createRowSizeCalculator(),
            ),
        );

        $txtTester = new CommandTester($txtCommand);
        $this->assertSame(1, $txtTester->execute(['--no-backup' => true], ['interactive' => false]));
        $this->assertStringContainsString('Your database version is not supported!', $txtTester->getDisplay());
        $this->assertStringContainsString('The database server is not configured properly.', $txtTester->getDisplay());

        $ndjsonCommand = $this->createCommand(
            $this->createRunner(
                $this->createCommandCompiler([], []),
                $this->createConnection('TRADITIONAL', '5.0.10'),
                $this->createMigrationCollection([]),
                $this->createBackupManager(),
                $this->createRowSizeCalculator(),
            ),
        );

        $ndjsonTester = new CommandTester($ndjsonCommand);
        $this->assertSame(1, $ndjsonTester->execute(['--format' => 'ndjson', '--no-backup' => true], ['interactive' => false]));
        $events = $this->jsonArrayFromNdjson($ndjsonTester->getDisplay());
        $this->assertCount(1, $events);
        $this->assertSame('problem', $events[0]['type']);
        $this->assertArrayHasKey('message', $events[0]);
        $this->assertArrayNotHasKey('severity', $events[0]);
    }

    public function testMigrationsOnlyBackupSkipForDropOnlySchemaChanges(): void
    {
        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->method('createCreateConfig')
            ->willReturn(new CreateConfig(new Backup('valid_backup_filename__20211101141254.sql')))
        ;

        $backupManager
            ->expects($this->never())
            ->method('create')
        ;

        $commandCompiler = $this->createCommandCompiler(['DROP TABLE tl_test'], []);

        $command = $this->createCommand(
            $this->createRunner(
                $commandCompiler,
                $this->createConnection(),
                $this->createMigrationCollection([]),
                $backupManager,
                $this->createRowSizeCalculator(),
            ),
        );

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute(['--migrations-only' => true], ['interactive' => false]));
        $this->assertStringContainsString('Database dump skipped because there are no migrations to execute.', $tester->getDisplay());
    }

    public function testSchemaOnlyDropOnlyCommandsPrintZeroSqlQueries(): void
    {
        $commandCompiler = $this->createCommandCompiler(['DROP TABLE tl_test'], []);

        $command = $this->createCommand(
            $this->createRunner(
                $commandCompiler,
                $this->createConnection(),
                $this->createMigrationCollection([]),
                $this->createBackupManager(),
                $this->createRowSizeCalculator(),
            ),
        );

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute(['--schema-only' => true, '--no-backup' => true], ['interactive' => false]));
        $this->assertStringContainsString('Executed 0 SQL queries.', $tester->getDisplay());
    }

    public function testUnexpectedPendingMigrationTxtRestartFormatting(): void
    {
        $migrations = $this->createStub(MigrationCollection::class);
        $migrations
            ->method('hasPending')
            ->willReturn(true)
        ;

        $migrations
            ->method('getPendingNames')
            ->willReturnOnConsecutiveCalls(['Migration 1', 'Migration 2'], [])
        ;
        $migrations
            ->method('run')
            ->willReturnCallback(
                static function (): \Generator {
                    yield new MigrationResult(true, 'Result 1');

                    throw new UnexpectedPendingMigrationException('Expected "Foo" got "Bar".');
                },
            )
        ;

        $command = $this->createCommand(
            $this->createRunner(
                $this->createCommandCompiler([], []),
                $this->createConnection(),
                $migrations,
                $this->createBackupManager(),
                $this->createRowSizeCalculator(),
            ),
        );

        $tester = new CommandTester($command);
        $tester->execute(['--migrations-only' => true, '--no-backup' => true], ['interactive' => false]);

        $display = $tester->getDisplay();

        $this->assertStringContainsString('Executed 1 migrations.', $display);
        $this->assertStringContainsString('Expected "Foo" got "Bar".', $display);
        $this->assertStringContainsString('Restarting migration process...', $display);
    }

    public static function provideInvalidOptionCombinations(): iterable
    {
        yield 'unsupported format' => [
            ['--format' => 'xml'],
            'Unsupported format "xml".',
        ];

        yield 'migrations only with schema only' => [
            ['--migrations-only' => true, '--schema-only' => true],
            '--migrations-only cannot be combined with --schema-only',
        ];

        yield 'migrations only with deletes' => [
            ['--migrations-only' => true, '--with-deletes' => true],
            '--migrations-only cannot be combined with --with-deletes',
        ];
    }

    private function createCommand(DatabaseMigrationRunner $runner): MigrateCommand
    {
        return new MigrateCommand($runner);
    }

    private function createStubCommand(\Closure|null $callback = null): MigrateCommand
    {
        $runner = $this->createStub(DatabaseMigrationRunner::class);
        $runner
            ->method('run')
            ->willReturnCallback($callback ?? DatabaseMigrationResult::success(...))
        ;

        return $this->createCommand($runner);
    }

    private function createRunner(CommandCompiler $commandCompiler, Connection $connection, MigrationCollection $migrations, BackupManager $backupManager, MysqlInnodbRowSizeCalculator $rowSizeCalculator): DatabaseMigrationRunner
    {
        return new DatabaseMigrationRunner($commandCompiler, $connection, $migrations, $backupManager, $rowSizeCalculator);
    }

    private function createMigrationCollection(array $pendingNames): MigrationCollection
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
            ->willReturn([new MigrationResult(true, 'Result 1')])
        ;

        return $migrations;
    }

    private function createBackupManager(): BackupManager
    {
        $backupManager = $this->createStub(BackupManager::class);
        $backupManager
            ->method('createCreateConfig')
            ->willReturn(new CreateConfig(new Backup('valid_backup_filename__20211101141254.sql')))
        ;

        $backupManager
            ->method('create')
            ->willReturnCallback(
                static function (): void {
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

    private function createConnection(string $sqlMode = 'TRADITIONAL', string $serverVersion = '8.0.0'): Connection
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('getParams')
            ->willReturn([])
        ;

        $connection
            ->method('fetchOne')
            ->willReturnCallback(static fn (string $query): int|string|false => match ($query) {
                'SELECT @@sql_mode' => $sqlMode,
                'SELECT @@version' => $serverVersion,
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

    private function createSchemaWithBigTable(): Schema
    {
        $schema = new Schema();
        $table = $schema->createTable('tl_test');
        $table->addOption('engine', 'InnoDB');

        return $schema;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function jsonArrayFromNdjson(string $ndjson): array
    {
        return array_map(static fn (string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR), explode("\n", trim($ndjson)));
    }
}

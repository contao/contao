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
use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Contao\CoreBundle\Doctrine\Schema\MysqlInnodbRowSizeCalculator;
use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InstallationBundle\Database\Installer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\Schema;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class MigrateCommandTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    public function testAbortsEarlyIfTheBackupFails(): void
    {
        $backupManager = $this->createBackupManager(true);
        $backupManager
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new BackupManagerException('Something went terribly wrong.'))
        ;

        $command = $this->getCommand([], [], [], null, $backupManager);
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(1, $code);
        $this->assertDoesNotMatchRegularExpression('/All migrations completed/', $display);
    }

    /**
     * @dataProvider getOutputFormats
     */
    public function testExecutesWithoutPendingMigrations(string $format): void
    {
        $command = $this->getCommand();
        $tester = new CommandTester($command);
        $code = $tester->execute(['--format' => $format, '--no-backup' => true], ['interactive' => 'ndjson' !== $format]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);

        if ('ndjson' === $format) {
            $this->assertSame(
                [
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'schema-pending', 'commands' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertMatchesRegularExpression('/All migrations completed/', $display);
        }
    }

    /**
     * @dataProvider getOutputFormatsAndBackup
     */
    public function testExecutesPendingMigrations(string $format, bool $backupsEnabled): void
    {
        $command = $this->getCommand(
            [['Migration 1', 'Migration 2']],
            [[new MigrationResult(true, 'Result 1'), new MigrationResult(true, 'Result 2')]],
            [],
            null,
            $this->createBackupManager($backupsEnabled)
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $code = $tester->execute(['--format' => $format, '--no-backup' => !$backupsEnabled], ['interactive' => 'ndjson' !== $format]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);

        if ('ndjson' === $format) {
            $expected = [];

            if ($backupsEnabled) {
                $expected[] = ['type' => 'backup-result', 'createdAt' => '2021-11-01T14:12:54+00:00', 'size' => 0, 'name' => 'valid_backup_filename__20211101141254.sql'];
            }

            $expected = array_merge(
                $expected,
                [
                    ['type' => 'migration-pending', 'names' => ['Migration 1', 'Migration 2'], 'hash' => 'ba37bf15c565f47d20df024e3f18bd32e88985525920011c4669c574d71b69fd'],
                    ['type' => 'migration-result', 'message' => 'Result 1', 'isSuccessful' => true],
                    ['type' => 'migration-result', 'message' => 'Result 2', 'isSuccessful' => true],
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'schema-pending', 'commands' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                ],
            );

            $this->assertSame($expected, $this->jsonArrayFromNdjson($display));
        } else {
            if ($backupsEnabled) {
                $this->assertStringContainsString('Creating a database dump', $display);
            }

            $this->assertStringContainsString('Migration 1', $display);
            $this->assertStringContainsString('Migration 2', $display);
            $this->assertStringContainsString('Result 1', $display);
            $this->assertStringContainsString('Result 2', $display);
            $this->assertStringContainsString('Executed 2 migrations', $display);
            $this->assertStringContainsString('All migrations completed', $display);
        }
    }

    /**
     * @group legacy
     * @dataProvider getOutputFormats
     */
    public function testExecutesRunOnceFiles(string $format): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.9: Using "runonce.php" files has been deprecated %s.');

        $runOnceFile = Path::join($this->getTempDir(), 'runonceFile.php');

        (new Filesystem())->dumpFile($runOnceFile, '<?php $GLOBALS["test_'.self::class.'"] = "executed";');

        $command = $this->getCommand([], [], [[$runOnceFile]]);

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $code = $tester->execute(['--format' => $format, '--no-backup' => true], ['interactive' => 'ndjson' !== $format]);
        $display = $tester->getDisplay();

        $this->assertSame('executed', $GLOBALS['test_'.self::class]);

        unset($GLOBALS['test_'.self::class]);

        $this->assertSame(0, $code);

        if ('ndjson' === $format) {
            $this->assertSame(
                [
                    [
                        'type' => 'migration-pending',
                        'names' => ['Runonce file: runonceFile.php'],
                        'hash' => '1ff509c324643092e7d68c763d03832e4b96f5be8fa3a95ea6765abfe96443ca',
                    ],
                    ['type' => 'migration-result', 'message' => 'Executed runonce file: runonceFile.php', 'isSuccessful' => true],
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'schema-pending', 'commands' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertMatchesRegularExpression('/runonceFile.php/', $display);
            $this->assertMatchesRegularExpression('/All migrations completed/', $display);
            $this->assertFileDoesNotExist($runOnceFile, 'File should be gone once executed');
        }
    }

    /**
     * @dataProvider getOutputFormats
     */
    public function testExecutesSchemaDiff(string $format): void
    {
        $installer = $this->createMock(Installer::class);
        $installer
            ->expects($this->atLeastOnce())
            ->method('compileCommands')
        ;

        $installer
            ->expects($this->atLeastOnce())
            ->method('getCommands')
            ->with(false)
            ->willReturn(
                [
                    'hash1' => 'First call QUERY 1',
                    'hash2' => 'First call QUERY 2',
                ],
                [
                    'hash3' => 'Second call QUERY 1',
                    'hash4' => 'Second call QUERY 2',
                    'hash5' => 'DROP QUERY',
                ],
                []
            )
        ;

        $command = $this->getCommand([], [], [], $installer);

        $tester = new CommandTester($command);
        $tester->setInputs(['yes', 'yes']);

        $code = $tester->execute(['--format' => $format, '--no-backup' => true], ['interactive' => 'ndjson' !== $format]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);

        if ('ndjson' === $format) {
            $this->assertSame(
                [
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'schema-pending', 'commands' => ['First call QUERY 1', 'First call QUERY 2'], 'hash' => 'f8e23e09e1009f794eabb39a6883800162ff828f9a1ccf0c5920fd646204fe58'],
                    ['type' => 'schema-execute', 'command' => 'First call QUERY 1'],
                    ['type' => 'schema-result', 'command' => 'First call QUERY 1', 'isSuccessful' => true],
                    ['type' => 'schema-execute', 'command' => 'First call QUERY 2'],
                    ['type' => 'schema-result', 'command' => 'First call QUERY 2', 'isSuccessful' => true],
                    ['type' => 'schema-pending', 'commands' => ['Second call QUERY 1', 'Second call QUERY 2', 'DROP QUERY'], 'hash' => '1cde239fb3063750c8594c21d522b2372d86547d96672f1823f782083f70c788'],
                    ['type' => 'schema-execute', 'command' => 'Second call QUERY 1'],
                    ['type' => 'schema-result', 'command' => 'Second call QUERY 1', 'isSuccessful' => true],
                    ['type' => 'schema-execute', 'command' => 'Second call QUERY 2'],
                    ['type' => 'schema-result', 'command' => 'Second call QUERY 2', 'isSuccessful' => true],
                    ['type' => 'schema-pending', 'commands' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertMatchesRegularExpression('/First call QUERY 1/', $display);
            $this->assertMatchesRegularExpression('/First call QUERY 2/', $display);
            $this->assertMatchesRegularExpression('/Second call QUERY 1/', $display);
            $this->assertMatchesRegularExpression('/Second call QUERY 2/', $display);
            $this->assertMatchesRegularExpression('/Executed 2 SQL queries/', $display);
            $this->assertDoesNotMatchRegularExpression('/Executed 3 SQL queries/', $display);
            $this->assertMatchesRegularExpression('/All migrations completed/', $display);
        }
    }

    /**
     * @group legacy
     * @dataProvider getOutputFormats
     */
    public function testDoesNotExecuteWithDryRun(string $format): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.9: Using "runonce.php" files has been deprecated %s.');

        $installer = $this->createMock(Installer::class);
        $installer
            ->expects($this->once())
            ->method('compileCommands')
        ;

        $installer
            ->expects($this->once())
            ->method('getCommands')
            ->with(false)
            ->willReturn(
                [
                    'hash1' => 'First call QUERY 1',
                    'hash2' => 'First call QUERY 2',
                ]
            )
        ;

        $runOnceFile = Path::join($this->getTempDir(), 'runonceFile.php');

        (new Filesystem())->dumpFile($runOnceFile, '<?php $GLOBALS["test_'.self::class.'"] = "executed";');

        $command = $this->getCommand(
            [['Migration 1', 'Migration 2']],
            [[new MigrationResult(true, 'Result 1'), new MigrationResult(true, 'Result 2')]],
            [[$runOnceFile]],
            $installer
        );

        $tester = new CommandTester($command);

        // No --no-backup here because --dry-run should automatically disable backups
        $code = $tester->execute(['--dry-run' => true, '--format' => $format]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);

        if ('ndjson' === $format) {
            $this->assertSame(
                [
                    [
                        'type' => 'migration-pending',
                        'names' => ['Migration 1', 'Migration 2', 'Runonce file: runonceFile.php'],
                        'hash' => 'fd96e0795abea843b443ccd39c746b5f1491c45131611f14a7c5bfb518824252',
                    ],
                    [
                        'type' => 'schema-pending',
                        'commands' => ['First call QUERY 1', 'First call QUERY 2'],
                        'hash' => 'f8e23e09e1009f794eabb39a6883800162ff828f9a1ccf0c5920fd646204fe58',
                    ],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertMatchesRegularExpression('/Migration 1/', $display);
            $this->assertMatchesRegularExpression('/Migration 2/', $display);
            $this->assertDoesNotMatchRegularExpression('/Result 1/', $display);
            $this->assertDoesNotMatchRegularExpression('/Result 2/', $display);

            $this->assertMatchesRegularExpression('/runonceFile.php/', $display);
            $this->assertFileExists($runOnceFile, 'File should not be gone in dry-run mode');

            $this->assertMatchesRegularExpression('/First call QUERY 1/', $display);
            $this->assertMatchesRegularExpression('/First call QUERY 2/', $display);
            $this->assertDoesNotMatchRegularExpression('/Executed 2 SQL queries/', $display);

            $this->assertMatchesRegularExpression('/All migrations completed/', $display);
        }
    }

    public function testAbortsIfAnswerIsNo(): void
    {
        $command = $this->getCommand(
            [['Migration 1', 'Migration 2']],
            [[new MigrationResult(true, 'Result 1'), new MigrationResult(true, 'Result 2')]]
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['n']);

        $code = $tester->execute(['--no-backup' => true]);
        $display = $tester->getDisplay();

        $this->assertSame(1, $code);
        $this->assertMatchesRegularExpression('/Migration 1/', $display);
        $this->assertMatchesRegularExpression('/Migration 2/', $display);
        $this->assertDoesNotMatchRegularExpression('/Result 1/', $display);
        $this->assertDoesNotMatchRegularExpression('/Result 2/', $display);
        $this->assertDoesNotMatchRegularExpression('/All migrations completed/', $display);
    }

    /**
     * @dataProvider getOutputFormats
     */
    public function testDoesNotAbortIfMigrationFails(string $format): void
    {
        $command = $this->getCommand(
            [['Migration 1', 'Migration 2']],
            [[new MigrationResult(false, 'Result 1'), new MigrationResult(true, 'Result 2')]]
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $code = $tester->execute(['--format' => $format, '--no-backup' => true], ['interactive' => 'ndjson' !== $format]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);

        if ('ndjson' === $format) {
            $this->assertSame(
                [
                    ['type' => 'migration-pending', 'names' => ['Migration 1', 'Migration 2'], 'hash' => 'ba37bf15c565f47d20df024e3f18bd32e88985525920011c4669c574d71b69fd'],
                    ['type' => 'migration-result', 'message' => 'Result 1', 'isSuccessful' => false],
                    ['type' => 'migration-result', 'message' => 'Result 2', 'isSuccessful' => true],
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'schema-pending', 'commands' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                    ['type' => 'migration-pending', 'names' => [], 'hash' => '4f53cda18c2baa0c0354bb5f9a3ecbe5ed12ab4d8e11ba873c2f11161202b945'],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertMatchesRegularExpression('/Migration 1/', $display);
            $this->assertMatchesRegularExpression('/Migration 2/', $display);
            $this->assertMatchesRegularExpression('/Result 1/', $display);
            $this->assertMatchesRegularExpression('/Migration failed/', $display);
            $this->assertMatchesRegularExpression('/Result 2/', $display);
            $this->assertMatchesRegularExpression('/All migrations completed/', $display);
        }
    }

    /**
     * @dataProvider getOutputFormats
     */
    public function testDoesAbortOnFatalError(string $format): void
    {
        $installer = $this->createMock(Installer::class);
        $installer
            ->expects($this->atLeastOnce())
            ->method('compileCommands')
            ->willThrowException(new \Exception('Fatal'))
        ;

        $command = $this->getCommand([], [], [], $installer);
        $tester = new CommandTester($command);

        if ('ndjson' !== $format) {
            $this->expectException(\Exception::class);
        }

        $code = $tester->execute(['--format' => $format, '--no-backup' => true], ['interactive' => 'ndjson' !== $format]);
        $display = $tester->getDisplay();

        $this->assertSame(1, $code);

        $json = $this->jsonArrayFromNdjson($display)[1];

        $this->assertSame('error', $json['type']);
        $this->assertSame('Fatal', $json['message']);
    }

    /**
     * @dataProvider getOutputFormats
     *
     * @group legacy
     */
    public function testDoesAbortOnWrongServerVersion(string $format): void
    {
        $this->expectDeprecation('%sgetWrappedConnection method is deprecated%s');

        $platform = new MySQL57Platform();
        $driver = new Driver();

        $driverConnection = $this->createMock(ServerInfoAwareConnection::class);
        $driverConnection
            ->method('getServerVersion')
            ->willReturn('8.0.29')
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform)
        ;

        $connection
            ->method('getDriver')
            ->willReturn($driver)
        ;

        $connection
            ->method('getWrappedConnection')
            ->willReturn($driverConnection)
        ;

        $connection
            ->method('getParams')
            ->willReturn(['serverVersion' => '5.7.39'])
        ;

        $command = $this->getCommand([], [], [], null, null, $connection);
        $tester = new CommandTester($command);
        $errorMessage = 'Wrong database version configured, please set it to "8.0.29", currently set to "5.7.39"';

        $code = $tester->execute(['--format' => $format, '--no-backup' => true], ['interactive' => 'ndjson' !== $format]);
        $display = $tester->getDisplay();

        $this->assertSame(1, $code);

        if ('ndjson' === $format) {
            $json = $this->jsonArrayFromNdjson($display)[0];

            $this->assertSame('problem', $json['type']);
            $this->assertSame($errorMessage, $json['message']);
        } else {
            $this->assertSame('[ERROR] '.$errorMessage, trim(preg_replace('/\s*\n\s*/', ' ', $display)));
        }
    }

    public function getOutputFormats(): \Generator
    {
        yield ['txt'];
        yield ['ndjson'];
    }

    public function getOutputFormatsAndBackup(): \Generator
    {
        yield 'txt and backups enabled' => ['txt', true];
        yield 'txt and backups disabled' => ['txt', false];
        yield 'ndjson and backups enabled' => ['ndjson', true];
        yield 'ndjson and backups disabled' => ['ndjson', false];
    }

    /**
     * @param array<array<string>>          $pendingMigrations
     * @param array<array<MigrationResult>> $migrationResults
     * @param array<array<string>>          $runonceFiles
     */
    private function getCommand(array $pendingMigrations = [], array $migrationResults = [], array $runonceFiles = [], Installer $installer = null, BackupManager $backupManager = null, Connection $connection = null): MigrateCommand
    {
        $migrations = $this->createMock(MigrationCollection::class);

        $pendingMigrations[] = [];
        $pendingMigrations[] = [];
        $pendingMigrations[] = [];

        $migrations
            ->method('getPendingNames')
            ->willReturn(...$pendingMigrations)
        ;

        $migrationResults[] = [];

        $migrations
            ->method('run')
            ->willReturn(...$migrationResults)
        ;

        $runonceFiles[] = [];
        $runonceFiles[] = [];
        $duplicatedRunonceFiles = [];

        foreach ($runonceFiles as $runonceFile) {
            $duplicatedRunonceFiles[] = $runonceFile;
            $duplicatedRunonceFiles[] = $runonceFile;
        }

        $fileLocator = $this->createMock(FileLocator::class);
        $fileLocator
            ->method('locate')
            ->with('config/runonce.php', null, false)
            ->willReturn(...$duplicatedRunonceFiles)
        ;

        $schemaProvider = $this->createMock(SchemaProvider::class);
        $schemaProvider
            ->method('createSchema')
            ->willReturn(new Schema())
        ;

        if (null === $connection) {
            $connection = $this->createMock(Connection::class);
            $connection
                ->method('getDatabasePlatform')
                ->willReturn($this->createMock(AbstractPlatform::class))
            ;
        }

        return new MigrateCommand(
            $migrations,
            $fileLocator,
            $this->getTempDir(),
            $this->createMock(ContaoFramework::class),
            $backupManager ?? $this->createBackupManager(false),
            $schemaProvider,
            $this->createMock(MysqlInnodbRowSizeCalculator::class),
            $connection,
            $installer ?? $this->createMock(Installer::class)
        );
    }

    /**
     * @return BackupManager&MockObject
     */
    private function createBackupManager(bool $backupsEnabled): BackupManager
    {
        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->expects($backupsEnabled ? $this->once() : $this->never())
            ->method('createCreateConfig')
            ->willReturn(new CreateConfig(new Backup('valid_backup_filename__20211101141254.sql')))
        ;

        $backupManager
            ->expects($backupsEnabled ? $this->once() : $this->never())
            ->method('create')
        ;

        return $backupManager;
    }

    private function jsonArrayFromNdjson(string $ndjson): array
    {
        return array_map(static fn (string $line) => json_decode($line, true), explode("\n", trim($ndjson)));
    }
}

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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InstallationBundle\Database\Installer;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class MigrateCommandTest extends TestCase
{
    use ExpectDeprecationTrait;

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
            $this->assertEmpty(trim($display));
        } else {
            $this->assertRegExp('/All migrations completed/', $display);
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
            $backupsEnabled
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $code = $tester->execute(['--format' => $format, '--no-backup' => !$backupsEnabled], ['interactive' => 'ndjson' !== $format]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);

        if ('ndjson' === $format) {
            $expected = [];

            if ($backupsEnabled) {
                $expected[] = ['type' => 'backup-result', 'createdAt' => '2021-11-01T14:12:54+00:00', 'size' => 0, 'path' => 'valid_backup_filename__20211101141254.sql'];
            }

            $expected = array_merge(
                $expected,
                [
                    ['type' => 'migration-pending', 'name' => 'Migration 1'],
                    ['type' => 'migration-pending', 'name' => 'Migration 2'],
                    ['type' => 'migration-result', 'message' => 'Result 1', 'isSuccessful' => true],
                    ['type' => 'migration-result', 'message' => 'Result 2', 'isSuccessful' => true],
                ]
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
                    ['type' => 'migration-pending', 'name' => 'Runonce file: runonceFile.php'],
                    ['type' => 'migration-result', 'message' => 'Executed runonce file: runonceFile.php', 'isSuccessful' => true],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertRegExp('/runonceFile.php/', $display);
            $this->assertRegExp('/All migrations completed/', $display);
            $this->assertFileNotExists($runOnceFile, 'File should be gone once executed');
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
                    ['type' => 'schema-pending', 'commands' => ['First call QUERY 1', 'First call QUERY 2']],
                    ['type' => 'schema-execute', 'command' => 'First call QUERY 1'],
                    ['type' => 'schema-result', 'command' => 'First call QUERY 1', 'isSuccessful' => true],
                    ['type' => 'schema-execute', 'command' => 'First call QUERY 2'],
                    ['type' => 'schema-result', 'command' => 'First call QUERY 2', 'isSuccessful' => true],
                    ['type' => 'schema-pending', 'commands' => ['Second call QUERY 1', 'Second call QUERY 2', 'DROP QUERY']],
                    ['type' => 'schema-execute', 'command' => 'Second call QUERY 1'],
                    ['type' => 'schema-result', 'command' => 'Second call QUERY 1', 'isSuccessful' => true],
                    ['type' => 'schema-execute', 'command' => 'Second call QUERY 2'],
                    ['type' => 'schema-result', 'command' => 'Second call QUERY 2', 'isSuccessful' => true],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertRegExp('/First call QUERY 1/', $display);
            $this->assertRegExp('/First call QUERY 2/', $display);
            $this->assertRegExp('/Second call QUERY 1/', $display);
            $this->assertRegExp('/Second call QUERY 2/', $display);
            $this->assertRegExp('/Executed 2 SQL queries/', $display);
            $this->assertNotRegExp('/Executed 3 SQL queries/', $display);
            $this->assertRegExp('/All migrations completed/', $display);
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
                    ['type' => 'migration-pending', 'name' => 'Migration 1'],
                    ['type' => 'migration-pending', 'name' => 'Migration 2'],
                    ['type' => 'migration-pending', 'name' => 'Runonce file: runonceFile.php'],
                    [
                        'type' => 'schema-pending',
                        'commands' => ['First call QUERY 1', 'First call QUERY 2'],
                    ],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertRegExp('/Migration 1/', $display);
            $this->assertRegExp('/Migration 2/', $display);
            $this->assertNotRegExp('/Result 1/', $display);
            $this->assertNotRegExp('/Result 2/', $display);

            $this->assertRegExp('/runonceFile.php/', $display);
            $this->assertFileExists($runOnceFile, 'File should not be gone in dry-run mode');

            $this->assertRegExp('/First call QUERY 1/', $display);
            $this->assertRegExp('/First call QUERY 2/', $display);
            $this->assertNotRegExp('/Executed 2 SQL queries/', $display);

            $this->assertRegExp('/All migrations completed/', $display);
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
        $this->assertRegExp('/Migration 1/', $display);
        $this->assertRegExp('/Migration 2/', $display);
        $this->assertNotRegExp('/Result 1/', $display);
        $this->assertNotRegExp('/Result 2/', $display);
        $this->assertNotRegExp('/All migrations completed/', $display);
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
                    ['type' => 'migration-pending', 'name' => 'Migration 1'],
                    ['type' => 'migration-pending', 'name' => 'Migration 2'],
                    ['type' => 'migration-result', 'message' => 'Result 1', 'isSuccessful' => false],
                    ['type' => 'migration-result', 'message' => 'Result 2', 'isSuccessful' => true],
                ],
                $this->jsonArrayFromNdjson($display)
            );
        } else {
            $this->assertRegExp('/Migration 1/', $display);
            $this->assertRegExp('/Migration 2/', $display);
            $this->assertRegExp('/Result 1/', $display);
            $this->assertRegExp('/Migration failed/', $display);
            $this->assertRegExp('/Result 2/', $display);
            $this->assertRegExp('/All migrations completed/', $display);
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

        $json = $this->jsonArrayFromNdjson($display)[0];

        $this->assertSame('error', $json['type']);
        $this->assertSame('Fatal', $json['message']);
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
    private function getCommand(array $pendingMigrations = [], array $migrationResults = [], array $runonceFiles = [], Installer $installer = null, bool $backupsEnabled = false): MigrateCommand
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

        return new MigrateCommand(
            $migrations,
            $fileLocator,
            $this->getTempDir(),
            $this->createMock(ContaoFramework::class),
            $backupManager,
            $installer ?? $this->createMock(Installer::class)
        );
    }

    private function jsonArrayFromNdjson(string $ndjson): array
    {
        return array_map(static fn (string $line) => json_decode($line, true), explode("\n", trim($ndjson)));
    }
}

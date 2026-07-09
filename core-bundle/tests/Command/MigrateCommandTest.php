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
use Contao\CoreBundle\Migration\DatabaseMigrationRunner;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandTest extends TestCase
{
    public function testRejectsInvalidOptionCombinations(): void
    {
        $tester = new CommandTester($this->createCommand());

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('--migrations-only cannot be combined with --schema-only');

        $tester->execute(['--migrations-only' => true, '--schema-only' => true], ['interactive' => false]);
    }

    public function testRejectsNdjsonInInteractiveNonDryRunMode(): void
    {
        $tester = new CommandTester($this->createCommand());

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('Use --no-interaction or --dry-run together with --format=ndjson');

        $tester->execute(['--format' => 'ndjson', '--no-backup' => true], ['interactive' => true]);
    }

    public function testPrintsConfigurationErrorsInTxtAndNdjson(): void
    {
        $runner = $this->createRunner(['The configured collation is not supported!']);
        $tester = new CommandTester($this->createCommand($runner));
        $this->assertSame(1, $tester->execute(['--no-backup' => true], ['interactive' => false]));
        $this->assertStringContainsString('The configured collation is not supported!', $tester->getDisplay());

        $tester = new CommandTester($this->createCommand($runner));
        $this->assertSame(1, $tester->execute(['--format' => 'ndjson', '--no-backup' => true], ['interactive' => false]));
        $this->assertStringContainsString('problem', $tester->getDisplay());
    }

    public function testSuccessfulRunPrintsFinalMessage(): void
    {
        $runner = $this->createRunner();
        $runner
            ->method('hasWorkToDo')
            ->willReturn(false)
        ;
        $runner
            ->method('validateDatabaseVersion')
            ->willReturn(null)
        ;
        $runner
            ->method('getPendingMigrationNames')
            ->willReturn([])
        ;
        $runner
            ->method('compileSchemaCommands')
            ->willReturn([])
        ;
        $runner
            ->method('compileConfigurationWarnings')
            ->willReturn([])
        ;
        $runner
            ->method('compileSchemaWarnings')
            ->willReturn([])
        ;

        $tester = new CommandTester($this->createCommand($runner));

        $this->assertSame(0, $tester->execute(['--no-backup' => true], ['interactive' => false]));
        $this->assertStringContainsString('All migrations completed.', $tester->getDisplay());
    }

    public function testConvertsHashMismatchToInvalidOptionException(): void
    {
        $runner = $this->createRunner(pendingNames: ['Migration 1']);

        $tester = new CommandTester($this->createCommand($runner));

        $this->expectException(InvalidOptionException::class);
        $this->expectExceptionMessage('Specified hash "x" does not match the actual hash');

        $tester->execute(['--hash' => 'x', '--no-backup' => true], ['interactive' => false]);
    }

    private function createCommand(DatabaseMigrationRunner|null $runner = null): MigrateCommand
    {
        return new MigrateCommand($runner ?? $this->createRunner());
    }

    private function createRunner(array $configurationErrors = [], array $pendingNames = []): DatabaseMigrationRunner
    {
        $runner = $this->createMock(DatabaseMigrationRunner::class);
        $runner
            ->method('compileConfigurationErrors')
            ->willReturn($configurationErrors)
        ;
        $runner
            ->method('compileConfigurationWarnings')
            ->willReturn([])
        ;
        $runner
            ->method('compileSchemaWarnings')
            ->willReturn([])
        ;
        $runner
            ->method('validateDatabaseVersion')
            ->willReturn(null)
        ;
        $runner
            ->method('hasWorkToDo')
            ->willReturn(false)
        ;
        $runner
            ->method('getBackupFilename')
            ->willReturn('valid_backup_filename__20211101141254.sql')
        ;
        $runner
            ->method('createBackup')
            ->willReturn(['createdAt' => '2021-11-01T14:12:54+00:00', 'size' => 0, 'name' => 'valid_backup_filename__20211101141254.sql'])
        ;
        $runner
            ->method('getPendingMigrationNames')
            ->willReturn($pendingNames)
        ;
        $runner
            ->method('runMigrations')
            ->willReturn([])
        ;
        $runner
            ->method('compileSchemaCommands')
            ->willReturn([])
        ;

        return $runner;
    }
}

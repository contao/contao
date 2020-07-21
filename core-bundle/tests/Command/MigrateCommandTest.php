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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InstallationBundle\Database\Installer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class MigrateCommandTest extends TestCase
{
    public function testExecutesWithoutPendingMigrations(): void
    {
        $command = $this->getCommand();
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertRegExp('/All migrations completed/', $display);
    }

    public function testExecutesPendingMigrations(): void
    {
        $command = $this->getCommand(
            [['Migration 1', 'Migration 2']],
            [[new MigrationResult(true, 'Result 1'), new MigrationResult(true, 'Result 2')]]
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertRegExp('/Migration 1/', $display);
        $this->assertRegExp('/Migration 2/', $display);
        $this->assertRegExp('/Result 1/', $display);
        $this->assertRegExp('/Result 2/', $display);
        $this->assertRegExp('/Executed 2 migrations/', $display);
        $this->assertRegExp('/All migrations completed/', $display);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Since contao/core-bundle 4.9: Using "runonce.php" files has been deprecated %s.
     */
    public function testExecutesRunOnceFiles(): void
    {
        $runOnceFile = $this->getFixturesDir().'/runonceFile.php';

        (new Filesystem())->dumpFile($runOnceFile, '<?php $GLOBALS["test_'.self::class.'"] = "executed";');

        $command = $this->getCommand([], [], [[$runOnceFile]]);

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame('executed', $GLOBALS['test_'.self::class]);

        unset($GLOBALS['test_'.self::class]);

        $this->assertSame(0, $code);
        $this->assertRegExp('/runonceFile.php/', $display);
        $this->assertRegExp('/All migrations completed/', $display);
    }

    public function testExecutesSchemaDiff(): void
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

        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertRegExp('/First call QUERY 1/', $display);
        $this->assertRegExp('/First call QUERY 2/', $display);
        $this->assertRegExp('/Second call QUERY 1/', $display);
        $this->assertRegExp('/Second call QUERY 2/', $display);
        $this->assertRegExp('/Executed 2 SQL queries/', $display);
        $this->assertNotRegExp('/Executed 3 SQL queries/', $display);
        $this->assertRegExp('/All migrations completed/', $display);
    }

    public function testAbortsIfAnswerIsNo(): void
    {
        $command = $this->getCommand(
            [['Migration 1', 'Migration 2']],
            [[new MigrationResult(true, 'Result 1'), new MigrationResult(true, 'Result 2')]]
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['n']);

        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(1, $code);
        $this->assertRegExp('/Migration 1/', $display);
        $this->assertRegExp('/Migration 2/', $display);
        $this->assertNotRegExp('/Result 1/', $display);
        $this->assertNotRegExp('/Result 2/', $display);
        $this->assertNotRegExp('/All migrations completed/', $display);
    }

    public function testDoesNotAbortIfMigrationFails(): void
    {
        $command = $this->getCommand(
            [['Migration 1', 'Migration 2']],
            [[new MigrationResult(false, 'Result 1'), new MigrationResult(true, 'Result 2')]]
        );

        $tester = new CommandTester($command);
        $tester->setInputs(['y']);

        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertRegExp('/Migration 1/', $display);
        $this->assertRegExp('/Migration 2/', $display);
        $this->assertRegExp('/Result 1/', $display);
        $this->assertRegExp('/Migration failed/', $display);
        $this->assertRegExp('/Result 2/', $display);
        $this->assertRegExp('/All migrations completed/', $display);
    }

    /**
     * @param array<array<string>>          $pendingMigrations
     * @param array<array<MigrationResult>> $migrationResults
     * @param array<array<string>>          $runonceFiles
     */
    private function getCommand(array $pendingMigrations = [], array $migrationResults = [], array $runonceFiles = [], Installer $installer = null): MigrateCommand
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

        return new MigrateCommand(
            $migrations,
            $fileLocator,
            $this->getFixturesDir(),
            $this->createMock(ContaoFramework::class),
            $installer ?? $this->createMock(Installer::class)
        );
    }
}

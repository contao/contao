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
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Migration\Migrations;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InstallationBundle\Database\Installer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Tester\CommandTester;

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

    /**
     * @param string[]                   $pendingMigrations
     * @param MigrationResult[]          $migrationResults
     * @param string[]                   $runonceFiles
     * @param ContaoFramework&MockObject $framework
     * @param Installer&MockObject       $installer
     */
    private function getCommand(array $pendingMigrations = [], array $migrationResults = [], array $runonceFiles = [], ContaoFramework $framework = null, Installer $installer = null): MigrateCommand
    {
        $migrations = $this->createMock(Migrations::class);

        $migrations
            ->method('getPendingMigrations')
            ->willReturn($pendingMigrations)
        ;

        $migrations
            ->method('runMigrations')
            ->willReturn($migrationResults)
        ;

        $fileLocator = $this->createMock(FileLocator::class);
        $fileLocator
            ->method('locate')
            ->with('config/runonce.php', null, false)
            ->willReturn($runonceFiles)
        ;

        return new MigrateCommand(
            $migrations,
            $fileLocator,
            $this->getFixturesDir(),
            $framework ?? $this->createMock(ContaoFramework::class),
            $installer ?? $this->createMock(Installer::class)
        );
    }
}

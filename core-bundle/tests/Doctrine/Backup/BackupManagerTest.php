<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Doctrine\Backup;

use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Contao\CoreBundle\Doctrine\Backup\DumperInterface;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class BackupManagerTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (new Filesystem())->mkdir($this->getBackupDir());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove($this->getBackupDir());
    }

    public function testCreateCreateConfig(): void
    {
        $manager = $this->getBackupManager();

        $config = $manager->createCreateConfig();

        $this->assertSame(['foobar'], $config->getTablesToIgnore());
    }

    public function testCreateRestoreConfigThrowsIfNoBackupAvailableYet(): void
    {
        $this->expectException(BackupManagerException::class);
        $this->expectExceptionMessage('No backups found.');

        $manager = $this->getBackupManager();

        $manager->createRestoreConfig();
    }

    public function testCreateRestoreConfig(): void
    {
        $manager = $this->getBackupManager();

        $backup = Backup::createNewAtPath($this->getBackupDir());

        $manager->createRestoreConfig();

        $config = $manager->createCreateConfig();

        $this->assertSame(['foobar'], $config->getTablesToIgnore());
        $this->assertSame($backup->getFilepath(), $config->getBackup()->getFilepath());
    }

    public function testListBackupsInCorrectOrder(): void
    {
        $manager = $this->getBackupManager();

        $backupPastWeek = Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-1 week'));
        $backupNow = Backup::createNewAtPath($this->getBackupDir());
        $backupTwoWeeksAgo = Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-2 weeks'));

        $backups = $manager->listBackups();
        $this->assertCount(3, $backups);

        $this->assertSame($backups[0]->getFilepath(), $backupNow->getFilepath());
        $this->assertSame($backups[1]->getFilepath(), $backupPastWeek->getFilepath());
        $this->assertSame($backups[2]->getFilepath(), $backupTwoWeeksAgo->getFilepath());

        $latestBackup = $manager->getLatestBackup();

        $this->assertSame($latestBackup->getFilepath(), $backupNow->getFilepath());
    }

    public function testIgnoresFilesThatAreNoBackups(): void
    {
        $manager = $this->getBackupManager();

        // Wrong file extension
        (new Filesystem())->dumpFile($this->getBackupDir().'/backup__20211101141254.zip', '');

        // In subfolder
        (new Filesystem())->dumpFile($this->getBackupDir().'/subfolder/backup__20211101141254.sql', '');

        $this->assertCount(0, $manager->listBackups());
    }

    private function getBackupDir(): string
    {
        return Path::join($this->getTempDir(), 'backups');
    }

    private function getBackupManager(): BackupManager
    {
        return new BackupManager(
            $this->createMock(Connection::class),
            $this->createMock(DumperInterface::class),
            $this->getBackupDir(),
            ['foobar'],
            5,
        );
    }
}

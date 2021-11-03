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
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
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

    /**
     * @testWith [true]
     *           [false]
     */
    public function testSuccessfulCreate(bool $autoCommitEnabled): void
    {
        $connection = $this->getConnection($autoCommitEnabled);

        $dumper = $this->createMock(DumperInterface::class);
        $dumper
            ->expects($this->once())
            ->method('dump')
            ->with(
                $connection,
                $this->isInstanceOf(CreateConfig::class)
            )
        ;

        $manager = $this->getBackupManager($connection, $dumper);
        $config = $manager->createCreateConfig();

        $manager->create($config);
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function testUnsuccessfulCreate(bool $autoCommitEnabled): void
    {
        $this->expectException(BackupManagerException::class);
        $this->expectExceptionMessage('Error!');

        $connection = $this->getConnection($autoCommitEnabled);

        $dumper = $this->createMock(DumperInterface::class);
        $dumper
            ->expects($this->once())
            ->method('dump')
            ->with(
                $connection,
                $this->isInstanceOf(CreateConfig::class)
            )
            ->willThrowException(new BackupManagerException('Error!'))
        ;

        $manager = $this->getBackupManager($connection, $dumper);
        $config = $manager->createCreateConfig();

        $manager->create($config);
    }

    public function testDirectoryIsCleanedUpAfterSuccessfulCreate(): void
    {
        $dumper = $this->createMock(DumperInterface::class);
        $dumper
            ->expects($this->once())
            ->method('dump')
        ;
        $manager = $this->getBackupManager($this->getConnection(true), $dumper);
        $config = $manager->createCreateConfig();

        Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-1 day'));
        Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-2 days'));
        Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-3 days'));
        Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-4 days'));
        $oldest = Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-1 week'));

        $manager->create($config);

        $this->assertCount(5, $manager->listBackups());
        $this->assertNotSame($oldest->getFilepath(), $manager->listBackups()[4]->getFilepath());
    }

    public function testDirectoryIsNotCleanedUpAfterUnsuccessfulCreate(): void
    {
        $dumper = $this->createMock(DumperInterface::class);
        $dumper
            ->expects($this->once())
            ->method('dump')
            ->willThrowException(new BackupManagerException('Error!'))
        ;
        $manager = $this->getBackupManager($this->getConnection(true), $dumper);
        $config = $manager->createCreateConfig();

        Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-1 day'));
        Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-2 days'));
        Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-3 days'));
        Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-4 days'));
        $oldest = Backup::createNewAtPath($this->getBackupDir(), new \DateTime('-1 week'));

        try {
            $manager->create($config);
        } catch (BackupManagerException $exception) {
            // irrelevant for this test
        }

        $this->assertCount(5, $manager->listBackups());
        $this->assertSame($oldest->getFilepath(), $manager->listBackups()[4]->getFilepath());
    }

    private function getConnection(bool $autoCommitEnabled): Connection
    {
        $connection = $this->getMockBuilder(Connection::class);
        $connection = $connection
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethodsExcept(['transactional'])
            ->getMock()
        ;

        $connection
            ->expects($this->once())
            ->method('isAutoCommit')
            ->willReturn($autoCommitEnabled)
        ;

        if ($autoCommitEnabled) {
            $connection
                ->expects($this->exactly(2))
                ->method('setAutoCommit')
                ->withConsecutive(
                    [false],
                    [true],
                )
            ;
        }

        return $connection;
    }

    private function getBackupDir(): string
    {
        return Path::join($this->getTempDir(), 'backups');
    }

    private function getBackupManager(Connection $connection = null, DumperInterface $dumper = null): BackupManager
    {
        $connection = $connection ?? $this->createMock(Connection::class);
        $dumper = $dumper ?? $this->createMock(DumperInterface::class);

        return new BackupManager(
            $connection,
            $dumper,
            $this->getBackupDir(),
            ['foobar'],
            5,
        );
    }
}

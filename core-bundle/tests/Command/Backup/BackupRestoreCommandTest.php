<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command\Backup;

use Contao\CoreBundle\Command\Backup\BackupRestoreCommand;
use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Contao\CoreBundle\Doctrine\Backup\Config\RestoreConfig;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;

class BackupRestoreCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    #[DataProvider('successfulCommandRunProvider')]
    public function testSuccessfulCommandRun(array $arguments, \Closure $expectedRestoreConfig, string $expectedOutput): void
    {
        $command = new BackupRestoreCommand($this->mockBackupManager($expectedRestoreConfig));

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute($arguments);
        $normalizedOutput = preg_replace('/\\s\\s+/', ' ', $commandTester->getDisplay(true));

        $this->assertStringContainsString($expectedOutput, $normalizedOutput);
        $this->assertSame(0, $code);
    }

    #[DataProvider('unsuccessfulCommandRunProvider')]
    public function testUnsuccessfulCommandRun(array $arguments, string $expectedOutput): void
    {
        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->expects($this->once())
            ->method('restore')
            ->willThrowException(new BackupManagerException('Some error.'))
        ;

        $command = new BackupRestoreCommand($backupManager);

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute($arguments);

        $this->assertStringContainsString($expectedOutput, $commandTester->getDisplay(true));
        $this->assertSame(1, $code);
    }

    public function testBackupSelection(): void
    {
        $command = new BackupRestoreCommand($this->mockBackupManagerWithBackups());

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['1']);

        $code = $commandTester->execute([]);

        $this->assertSame(0, $code);
    }

    public static function unsuccessfulCommandRunProvider(): iterable
    {
        yield 'Text format' => [
            [],
            '[ERROR] Some error.',
        ];

        yield 'JSON format' => [
            ['--format' => 'json'],
            '{"error":"Some error."}',
        ];
    }

    public static function successfulCommandRunProvider(): iterable
    {
        yield 'Default arguments' => [
            [],
            static fn (RestoreConfig $config) => [] === $config->getTablesToIgnore()
                && 'test__20211101141254.sql.gz' === $config->getBackup()->getFilename()
                && false === $config->ignoreOriginCheck(),
            '[OK] Successfully restored backup from "test__20211101141254.sql.gz".',
        ];

        yield 'Different tables to ignore' => [
            ['--ignore-tables' => 'foo,bar'],
            static fn (RestoreConfig $config) => ['bar', 'foo'] === $config->getTablesToIgnore()
                && 'test__20211101141254.sql.gz' === $config->getBackup()->getFilename()
                && false === $config->ignoreOriginCheck(),
            '[OK] Successfully restored backup from "test__20211101141254.sql.gz".',
        ];

        yield 'Specific backup' => [
            ['name' => 'file__20211101141254.sql'],
            static fn (RestoreConfig $config) => [] === $config->getTablesToIgnore()
                && 'file__20211101141254.sql' === $config->getBackup()->getFilename()
                && false === $config->ignoreOriginCheck(),
            '[OK] Successfully restored backup from "file__20211101141254.sql".',
        ];

        yield 'Force restore' => [
            ['--force' => true],
            static fn (RestoreConfig $config) => [] === $config->getTablesToIgnore()
                && 'test__20211101141254.sql.gz' === $config->getBackup()->getFilename()
                && $config->ignoreOriginCheck(),
            '[OK] Successfully restored backup from "test__20211101141254.sql.gz".',
        ];

        yield 'JSON format' => [
            ['--format' => 'json'],
            static fn (RestoreConfig $config) => [] === $config->getTablesToIgnore()
                && 'test__20211101141254.sql.gz' === $config->getBackup()->getFilename()
                && false === $config->ignoreOriginCheck(),
            '{"createdAt":"2021-11-01T14:12:54+00:00","size":100,"name":"test__20211101141254.sql.gz"}',
        ];

        yield 'No interaction' => [
            ['--no-interaction'],
            static fn (RestoreConfig $config) => [] === $config->getTablesToIgnore()
                && 'test__20211101141254.sql.gz' === $config->getBackup()->getFilename()
                && false === $config->ignoreOriginCheck(),
            '[OK] Successfully restored backup from "test__20211101141254.sql.gz".',
        ];
    }

    private function mockBackupManager(\Closure $expectedCreateConfig): BackupManager&MockObject
    {
        $backup = new Backup('test__20211101141254.sql.gz');
        $backup->setSize(100);

        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->expects($this->once())
            ->method('createRestoreConfig')
            ->willReturn(new RestoreConfig($backup))
        ;

        $backupManager
            ->expects($this->once())
            ->method('restore')
            ->with($this->callback($expectedCreateConfig))
        ;

        return $backupManager;
    }

    private function mockBackupManagerWithBackups(): BackupManager&MockObject
    {
        $backups = [
            $this->createBackup('foo__20250000000000.sql.gz', 50000),
            $this->createBackup('bar__20250000000000.sql.gz', 6005000),
        ];

        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->expects($this->once())
            ->method('listBackups')
            ->willReturn($backups)
        ;

        return $backupManager;
    }

    private function createBackup(string $filename, int $size): Backup
    {
        $backup = new Backup($filename);
        $backup->setSize($size);

        return $backup;
    }
}

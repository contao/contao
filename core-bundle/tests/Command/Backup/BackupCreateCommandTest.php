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

use Contao\CoreBundle\Command\Backup\BackupCreateCommand;
use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BackupCreateCommandTest extends TestCase
{
    /**
     * @dataProvider successfulCommandRunProvider
     */
    public function testSuccessfulCommandRun(array $arguments, \Closure $expectedCreateConfig, string $expectedOutput): void
    {
        $command = new BackupCreateCommand($this->createBackupManager($expectedCreateConfig));

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute($arguments);

        $this->assertStringContainsString($expectedOutput, $commandTester->getDisplay(true));
        $this->assertSame(0, $code);
    }

    /**
     * @dataProvider unsuccessfulCommandRunProvider
     */
    public function testUnsuccessfulCommandRun(array $arguments, string $expectedOutput): void
    {
        $backupManager = $this->createMock(BackupManager::class);
        $backupManager
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new BackupManagerException('Some error.'))
        ;

        $command = new BackupCreateCommand($backupManager);

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute($arguments);

        $this->assertStringContainsString($expectedOutput, $commandTester->getDisplay(true));
        $this->assertSame(1, $code);
    }

    public function unsuccessfulCommandRunProvider(): \Generator
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

    public function successfulCommandRunProvider(): \Generator
    {
        yield 'Default arguments' => [
            [],
            function (CreateConfig $config) {
                $this->assertSame(104857600, $config->getBufferSize()); // 100 MB
                $this->assertSame([], $config->getTablesToIgnore());
                $this->assertSame('test__20211101141254.sql.gz', $config->getBackup()->getFilepath());

                return true;
            },
            '[OK] Successfully created an SQL dump at "test__20211101141254.sql.gz".',
        ];

        yield 'Different buffer size' => [
            ['--buffer-size' => '30MB'],
            function (CreateConfig $config) {
                $this->assertSame(31457280, $config->getBufferSize()); // 30 MB
                $this->assertSame([], $config->getTablesToIgnore());
                $this->assertSame('test__20211101141254.sql.gz', $config->getBackup()->getFilepath());

                return true;
            },
            '[OK] Successfully created an SQL dump at "test__20211101141254.sql.gz".',
        ];

        yield 'Different tables to ignore' => [
            ['--ignore-tables' => 'foo,bar', '--buffer-size' => '2GB'],
            function (CreateConfig $config) {
                $this->assertSame(2147483648, $config->getBufferSize()); // 2 GB
                $this->assertSame(['foo', 'bar'], $config->getTablesToIgnore());
                $this->assertSame('test__20211101141254.sql.gz', $config->getBackup()->getFilepath());

                return true;
            },
            '[OK] Successfully created an SQL dump at "test__20211101141254.sql.gz".',
        ];

        yield 'Different target file' => [
            ['file' => 'somewhere/else/file__20211101141254.sql'],
            function (CreateConfig $config) {
                $this->assertSame(104857600, $config->getBufferSize()); // 2 GB
                $this->assertSame([], $config->getTablesToIgnore());
                $this->assertSame('somewhere/else/file__20211101141254.sql', $config->getBackup()->getFilepath());

                return true;
            },
            '[OK] Successfully created an SQL dump at "somewhere/else/file__20211101141254.sql".',
        ];

        yield 'JSON format' => [
            ['--format' => 'json'],
            function (CreateConfig $config) {
                $this->assertSame(104857600, $config->getBufferSize()); // 100 MB
                $this->assertSame([], $config->getTablesToIgnore());
                $this->assertSame('test__20211101141254.sql.gz', $config->getBackup()->getFilepath());

                return true;
            },
            '{"createdAt":"2021-11-01T14:12:54+0000","size":100,"humanReadableSize":"100 B","path":"test__20211101141254.sql.gz"}',
        ];
    }

    private function createBackupManager(\Closure $expectedCreateConfig): BackupManager
    {
        $backupManager = $this->createMock(BackupManager::class);

        $backup = $this->getMockBuilder(Backup::class)
            ->setConstructorArgs(['test__20211101141254.sql.gz'])
            ->onlyMethods(['getSize'])
        ;
        $backup = $backup->getMock();
        $backup
            ->method('getSize')
            ->willReturn(100)
        ;

        $backupManager
            ->expects($this->once())
            ->method('createCreateConfig')
            ->willReturn(new CreateConfig($backup))
        ;

        $backupManager
            ->expects($this->once())
            ->method('create')
            ->with($this->callback($expectedCreateConfig))
        ;

        return $backupManager;
    }
}

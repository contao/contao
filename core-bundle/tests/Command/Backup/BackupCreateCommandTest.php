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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;

class BackupCreateCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    #[DataProvider('successfulCommandRunProvider')]
    public function testSuccessfulCommandRun(array $arguments, \Closure $expectedCreateConfig, string $expectedOutput): void
    {
        $command = new BackupCreateCommand($this->mockBackupManager($expectedCreateConfig));

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute($arguments);
        $normalizedOutput = preg_replace('/\\s\\s+/', ' ', $commandTester->getDisplay(true));

        $this->assertStringContainsString($expectedOutput, $normalizedOutput);
        $this->assertSame(0, $code);
    }

    public static function successfulCommandRunProvider(): iterable
    {
        yield 'Default arguments' => [
            [],
            static fn (CreateConfig $config) => [] === $config->getTablesToIgnore()
                && 'test__20211101141254.sql.gz' === $config->getBackup()->getFilename(),
            '[OK] Successfully created SQL dump "test__20211101141254.sql.gz".',
        ];

        yield 'Different tables to ignore' => [
            ['--ignore-tables' => 'foo,bar'],
            static fn (CreateConfig $config) => ['bar', 'foo'] === $config->getTablesToIgnore()
                && 'test__20211101141254.sql.gz' === $config->getBackup()->getFilename(),
            '[OK] Successfully created SQL dump "test__20211101141254.sql.gz".',
        ];

        yield 'Different target file' => [
            ['name' => 'file__20211101141254.sql'],
            static fn (CreateConfig $config) => [] === $config->getTablesToIgnore()
                && 'file__20211101141254.sql' === $config->getBackup()->getFilename()
                && false === $config->isGzCompressionEnabled(),
            '[OK] Successfully created SQL dump "file__20211101141254.sql".',
        ];

        yield 'JSON format' => [
            ['--format' => 'json'],
            static fn (CreateConfig $config) => [] === $config->getTablesToIgnore()
                && 'test__20211101141254.sql.gz' === $config->getBackup()->getFilename(),
            '{"createdAt":"2021-11-01T14:12:54+00:00","size":100,"name":"test__20211101141254.sql.gz"}',
        ];
    }

    #[DataProvider('unsuccessfulCommandRunProvider')]
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
        $normalizedOutput = preg_replace("/\\s+\n/", "\n", $commandTester->getDisplay(true));

        $this->assertStringContainsString($expectedOutput, $normalizedOutput);
        $this->assertSame(1, $code);
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

    private function mockBackupManager(\Closure $expectedCreateConfig): BackupManager&MockObject
    {
        $backup = $this
            ->getMockBuilder(Backup::class)
            ->setConstructorArgs(['test__20211101141254.sql.gz'])
            ->onlyMethods(['getSize'])
            ->getMock()
        ;

        $backup
            ->method('getSize')
            ->willReturn(100)
        ;

        $backupManager = $this->createMock(BackupManager::class);
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

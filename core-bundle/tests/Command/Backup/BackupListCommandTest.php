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

use Contao\CoreBundle\Command\Backup\BackupListCommand;
use Contao\CoreBundle\Doctrine\Backup\Backup;
use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;

class BackupListCommandTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Table::class, Terminal::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider successfulCommandRunProvider
     */
    public function testSuccessfulCommandRun(array $arguments, string $expectedOutput): void
    {
        $command = new BackupListCommand($this->mockBackupManager());

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute($arguments);
        $normalizedOutput = preg_replace("/\\s+\n/", "\n", $commandTester->getDisplay(true));

        $expectedOutput = str_replace(
            '<TIMEZONE>',
            BackupListCommand::getFormattedTimeZoneOffset(new \DateTimeZone(date_default_timezone_get())),
            $expectedOutput,
        );

        $this->assertStringContainsString($expectedOutput, $normalizedOutput);
        $this->assertSame(0, $code);
    }

    public function successfulCommandRunProvider(): \Generator
    {
        yield 'Text format' => [
            [],
            <<<'OUTPUT'
                 --------------------- ----------- ------------------------------
                  Created (<TIMEZONE>)      Size        Name
                 --------------------- ----------- ------------------------------
                  2021-11-01 14:12:54   48.83 KiB   test__20211101141254.sql.gz
                  2021-10-31 14:12:54   5.73 MiB    test2__20211031141254.sql.gz
                  2021-11-02 14:12:54   2.64 MiB    test3__20211102141254.sql.gz
                 --------------------- ----------- ------------------------------
                OUTPUT,
        ];

        yield 'JSON format' => [
            ['--format' => 'json'],
            '[{"createdAt":"2021-11-01T14:12:54+00:00","size":50000,"name":"test__20211101141254.sql.gz"},{"createdAt":"2021-10-31T14:12:54+00:00","size":6005000,"name":"test2__20211031141254.sql.gz"},{"createdAt":"2021-11-02T14:12:54+00:00","size":2764922,"name":"test3__20211102141254.sql.gz"}]',
        ];
    }

    private function mockBackupManager(): BackupManager&MockObject
    {
        $backups = [
            $this->createBackup('test__20211101141254.sql.gz', 50000),
            $this->createBackup('test2__20211031141254.sql.gz', 6005000),
            $this->createBackup('test3__20211102141254.sql.gz', 2764922),
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

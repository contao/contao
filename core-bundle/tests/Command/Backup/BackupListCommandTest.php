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
use Symfony\Component\Console\Tester\CommandTester;

class BackupListCommandTest extends TestCase
{
    /**
     * @dataProvider successfulCommandRunProvider
     */
    public function testSuccessfulCommandRun(array $arguments, string $expectedOutput): void
    {
        $command = new BackupListCommand($this->createBackupManager());

        $commandTester = new CommandTester($command);
        $code = $commandTester->execute($arguments);
        $normalizedOutput = preg_replace("/\\s+\n/", "\n", $commandTester->getDisplay(true));

        $this->assertStringContainsString($expectedOutput, $normalizedOutput);
        $this->assertSame(0, $code);
    }

    public function successfulCommandRunProvider(): \Generator
    {
        yield 'Text format' => [
            [],
            <<<'OUTPUT'
                --------------------- ---------- ------------------------------
                  Created               Size       Path
                 --------------------- ---------- ------------------------------
                  2021-11-01 14:12:54   48.83 KB   test__20211101141254.sql.gz
                  2021-10-31 14:12:54   5.73 MB    test2__20211031141254.sql.gz
                  2021-11-02 14:12:54   2.64 MB    test3__20211102141254.sql.gz
                 --------------------- ---------- ------------------------------
                OUTPUT
        ];

        yield 'JSON format' => [
            ['--format' => 'json'],
            '[{"createdAt":"2021-11-01T14:12:54+0000","size":50000,"humanReadableSize":"48.83 KB","path":"test__20211101141254.sql.gz"},{"createdAt":"2021-10-31T14:12:54+0000","size":6005000,"humanReadableSize":"5.73 MB","path":"test2__20211031141254.sql.gz"},{"createdAt":"2021-11-02T14:12:54+0000","size":2764922,"humanReadableSize":"2.64 MB","path":"test3__20211102141254.sql.gz"}]',
        ];
    }

    private function createBackupManager(): BackupManager
    {
        $backupManager = $this->createMock(BackupManager::class);

        $backups = [];
        $backups[] = $this->createBackup('test__20211101141254.sql.gz', 50000);
        $backups[] = $this->createBackup('test2__20211031141254.sql.gz', 6005000);
        $backups[] = $this->createBackup('test3__20211102141254.sql.gz', 2764922);

        $backupManager
            ->expects($this->once())
            ->method('listBackups')
            ->willReturn($backups)
        ;

        return $backupManager;
    }

    private function createBackup(string $filepath, int $size): Backup
    {
        $backup = $this->getMockBuilder(Backup::class)
            ->setConstructorArgs([$filepath])
            ->onlyMethods(['getSize'])
        ;
        $backup = $backup->getMock();
        $backup
            ->method('getSize')
            ->willReturn($size)
        ;

        return $backup;
    }
}

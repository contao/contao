<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Command;

use Contao\ManagerBundle\Command\MaintenanceModeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class MaintenanceModeCommandTest extends TestCase
{
    /**
     * @testWith [true, true]
     *           [false, true]
     *           [false, false]
     */
    public function testEnable(bool $alreadyEnabled, bool $maintenanceTemplateCustomized): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableAutoReturnValueGeneration() // Ensure we don't call any other method other than the ones we mock
            ->getMock()
        ;

        if ($alreadyEnabled) {
            $filesystem
                ->expects($this->once())
                ->method('exists')
                ->with('/path/to/webdir/maintenance.html')
                ->willReturn(true)
            ;
        } else {
            $filesystem
                ->expects($this->exactly(2))
                ->method('exists')
                ->withConsecutive(
                    ['/path/to/webdir/maintenance.html'],
                    ['/path/to/webdir/.maintenance.html']
                )
                ->willReturnOnConsecutiveCalls(
                    false,
                    $maintenanceTemplateCustomized
                )
            ;
        }

        if (!$alreadyEnabled) {
            if ($maintenanceTemplateCustomized) {
                $filesystem
                    ->expects($this->once())
                    ->method('rename')
                    ->with('/path/to/webdir/.maintenance.html', '/path/to/webdir/maintenance.html', true)
                ;
            } else {
                $filesystem
                    ->expects($this->once())
                    ->method('copy')
                    ->with(
                        Path::makeAbsolute('../../src/Resources/skeleton/public/.maintenance.html', __DIR__),
                        '/path/to/webdir/maintenance.html'
                    )
                ;
            }
        }

        $command = new MaintenanceModeCommand('/path/to/webdir', $filesystem);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['state' => 'enable']);

        $this->assertStringContainsString('[OK] Maintenance mode enabled', $commandTester->getDisplay(true));
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function testDisable(bool $alreadyDisabled): void
    {
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableAutoReturnValueGeneration() // Ensure we don't call any other method other than the ones we mock
            ->getMock()
        ;

        $filesystem
            ->expects($this->once())
            ->method('exists')
            ->with('/path/to/webdir/maintenance.html')
            ->willReturn(!$alreadyDisabled)
        ;

        $filesystem
            ->expects($alreadyDisabled ? $this->never() : $this->once())
            ->method('rename')
            ->with('/path/to/webdir/maintenance.html', '/path/to/webdir/.maintenance.html', true)
            ;

        $command = new MaintenanceModeCommand('/path/to/webdir', $filesystem);

        $commandTester = new CommandTester($command);
        $commandTester->execute(['state' => 'disable']);

        $this->assertStringContainsString('[OK] Maintenance mode disabled', $commandTester->getDisplay(true));
    }
}

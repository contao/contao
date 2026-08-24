<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cron;

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Cron\ResizeImagesCron;
use Contao\CoreBundle\Exception\CronExecutionSkippedException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\ProcessUtil;
use Contao\Image\DeferredImageStorageInterface;
use Symfony\Component\Process\Process;

class ResizeImagesCronTest extends TestCase
{
    public function testIsSkippedIfNotOnCli(): void
    {
        $cron = new ResizeImagesCron(
            new ProcessUtil('bin/console'),
            $this->createStub(DeferredImageStorageInterface::class),
        );

        $this->expectException(CronExecutionSkippedException::class);

        $cron(Cron::SCOPE_WEB);
    }

    public function testIsSkippedWithoutDeferredImages(): void
    {
        $storage = $this->createStub(DeferredImageStorageInterface::class);
        $storage
            ->method('listPaths')
            ->willReturn(new \ArrayIterator())
        ;

        $cron = new ResizeImagesCron(new ProcessUtil('bin/console'), $storage);

        $this->expectException(CronExecutionSkippedException::class);

        $cron(Cron::SCOPE_CLI);
    }

    public function testRunsImageResizingWithLimitedConcurrency(): void
    {
        $process = $this->createMock(Process::class);
        $process
            ->expects($this->once())
            ->method('setTimeout')
            ->with(null)
        ;

        $processUtil = $this->createMock(ProcessUtil::class);
        $processUtil
            ->expects($this->once())
            ->method('createSymfonyConsoleProcess')
            ->with('contao:resize-images', '--time-limit=50', '--concurrent=1')
            ->willReturn($process)
        ;

        $processUtil
            ->expects($this->once())
            ->method('createPromise')
            ->with($process)
        ;

        $storage = $this->createStub(DeferredImageStorageInterface::class);
        $storage
            ->method('listPaths')
            ->willReturn(new \ArrayIterator(['target.jpg']))
        ;

        $cron = new ResizeImagesCron($processUtil, $storage);
        $cron(Cron::SCOPE_CLI);
    }
}

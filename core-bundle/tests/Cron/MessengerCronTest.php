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
use Contao\CoreBundle\Cron\MessengerCron;
use Contao\CoreBundle\Exception\CronExecutionSkippedException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\ProcessUtil;
use Symfony\Component\Process\Process;

class MessengerCronTest extends TestCase
{
    public function testIsSkippedIfNotOnCli(): void
    {
        $cron = new MessengerCron(new ProcessUtil('bin/console'));

        $this->expectException(CronExecutionSkippedException::class);

        $cron(Cron::SCOPE_WEB);
    }

    public function testReturnsCorrectPromise(): void
    {
        $process = $this->createMock(Process::class);

        $processUtil = $this->createMock(ProcessUtil::class);
        $processUtil
            ->expects($this->once())
            ->method('createSymfonyConsoleProcess')
            ->with('contao:supervise-workers')
            ->willReturn($process)
        ;

        $processUtil
            ->expects($this->once())
            ->method('createPromise')
            ->with($process)
        ;

        $cron = new MessengerCron($processUtil);
        $cron(Cron::SCOPE_CLI);
    }
}

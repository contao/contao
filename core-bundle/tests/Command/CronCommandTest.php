<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\CronCommand;
use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CronCommandTest extends TestCase
{
    public function testRunsCronService(): void
    {
        $cron = $this->createMock(Cron::class);
        $cron
            ->expects($this->once())
            ->method('run')
            ->with(Cron::SCOPE_CLI)
        ;

        $command = new CronCommand($cron);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }
}

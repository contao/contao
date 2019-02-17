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

use Contao\CoreBundle\Command\AutomatorCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockInterface;

class AutomatorCommandTest extends CommandTestCase
{
    public function testIsLockedWhileRunning(): void
    {
        $command = $this->mockCommand(true);
        $tester = new CommandTester($command);
        $tester->setInputs(["\n"]);

        $code = $tester->execute(['command' => $command->getName()]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());
    }

    public function testHandlesAnInvalidSelection(): void
    {
        $command = $this->mockCommand();
        $tester = new CommandTester($command);
        $tester->setInputs(["4800\n"]);

        $code = $tester->execute(['command' => $command->getName()]);

        $this->assertSame(1, $code);
        $this->assertContains('Value "4800" is invalid (see help contao:automator)', $tester->getDisplay());
    }

    public function testHandlesAnInvalidTaskName(): void
    {
        $command = $this->mockCommand();

        $input = [
            'command' => $command->getName(),
            'task' => 'fooBar',
        ];

        $tester = new CommandTester($command);
        $code = $tester->execute($input);

        $this->assertSame(1, $code);
        $this->assertContains('Invalid task "fooBar" (see help contao:automator)', $tester->getDisplay());
    }

    private function mockCommand(bool $isLocked = false): AutomatorCommand
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->expects($this->once())
            ->method('acquire')
            ->willReturn(!$isLocked)
        ;

        $lock
            ->expects($isLocked ? $this->never() : $this->once())
            ->method('release')
        ;

        $command = new AutomatorCommand($this->mockContaoFramework(), $lock);
        $command->setApplication($this->mockApplication());

        return $command;
    }
}

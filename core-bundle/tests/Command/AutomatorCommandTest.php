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

class AutomatorCommandTest extends CommandTestCase
{
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

    private function mockCommand(): AutomatorCommand
    {
        $command = new AutomatorCommand($this->mockContaoFramework());
        $command->setApplication($this->mockApplication());

        return $command;
    }
}

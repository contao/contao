<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\AutomatorCommand;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\HttpKernel\KernelInterface;

class AutomatorCommandTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $command = new AutomatorCommand('contao:automator');

        $this->assertInstanceOf('Contao\CoreBundle\Command\AutomatorCommand', $command);
        $this->assertSame('contao:automator', $command->getName());
    }

    public function testGeneratesTheTaskList(): void
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->mockApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);
        $tester->setInputs(["\n"]);

        $code = $tester->execute(['command' => $command->getName()]);
        $output = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertContains('Please select a task:', $output);
        $this->assertContains('[10]', $output);
    }

    public function testCanBeConvertedToString(): void
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setFramework($this->mockContaoFramework());

        $this->assertContains('The name of the task:', $command->__toString());
    }

    public function testIsLockedWhileRunning(): void
    {
        $lock = new LockHandler('contao:automator', sys_get_temp_dir().'/'.md5('foobar'));
        $lock->lock();

        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->mockApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);
        $tester->setInputs(["\n"]);

        $code = $tester->execute(['command' => $command->getName()]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }

    public function testTakesTheTaskNameAsArgument(): void
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->mockApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);

        $code = $tester->execute([
            'command' => $command->getName(),
            'task' => 'purgeTempFolder',
        ]);

        $this->assertSame(0, $code);
    }

    public function testHandlesAnInvalidSelection(): void
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->mockApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);
        $tester->setInputs(["4800\n"]);

        $code = $tester->execute(['command' => $command->getName()]);

        $this->assertSame(1, $code);
        $this->assertContains('Value "4800" is invalid (see help contao:automator)', $tester->getDisplay());
    }

    public function testHandlesAnInvalidTaskName(): void
    {
        $command = new AutomatorCommand('contao:automator');
        $command->setApplication($this->mockApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);

        $code = $tester->execute([
            'command' => $command->getName(),
            'task' => 'fooBar',
        ]);

        $this->assertSame(1, $code);
        $this->assertContains('Invalid task "fooBar" (see help contao:automator)', $tester->getDisplay());
    }

    /**
     * Mocks the application.
     *
     * @return Application
     */
    private function mockApplication(): Application
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', 'foobar');

        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        $application = new Application($kernel);
        $application->setCatchExceptions(true);

        return $application;
    }
}

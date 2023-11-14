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
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class AutomatorCommandTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    public function testHandlesAnInvalidSelection(): void
    {
        $command = $this->getCommand();
        $tester = new CommandTester($command);
        $tester->setInputs(["4800\n"]);

        $code = $tester->execute(['command' => $command->getName()]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Value "4800" is invalid (see help contao:automator)', $tester->getDisplay());
    }

    public function testHandlesAnInvalidTaskName(): void
    {
        $command = $this->getCommand();

        $input = [
            'command' => $command->getName(),
            'task' => 'fooBar',
        ];

        $tester = new CommandTester($command);
        $code = $tester->execute($input);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Invalid task "fooBar" (see help contao:automator)', $tester->getDisplay());
    }

    private function getCommand(): AutomatorCommand
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getFixturesDir());
        $container->set('filesystem', new Filesystem());

        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        $application = new Application($kernel);
        $application->setCatchExceptions(true);

        $command = new AutomatorCommand($this->mockContaoFramework());
        $command->setApplication($application);

        return $command;
    }
}

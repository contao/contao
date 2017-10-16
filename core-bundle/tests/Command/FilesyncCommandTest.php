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

use Contao\CoreBundle\Command\FilesyncCommand;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class FilesyncCommandTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $command = new FilesyncCommand('contao:filesync');

        $this->assertInstanceOf('Contao\CoreBundle\Command\FilesyncCommand', $command);
        $this->assertSame('contao:filesync', $command->getName());
    }

    public function testOutputsTheConfirmationMessage(): void
    {
        $command = new FilesyncCommand('contao:filesync');
        $command->setApplication($this->mockApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(0, $code);
        $this->assertContains('Synchronization complete (see sync.log).', $tester->getDisplay());
    }

    public function testIsLockedWhileRunning(): void
    {
        $factory = new Factory(new FlockStore(sys_get_temp_dir().'/'.md5('foobar')));

        $lock = $factory->createLock('contao:filesync');
        $lock->acquire();

        $command = new FilesyncCommand('contao:filesync');
        $command->setApplication($this->mockApplication());
        $command->setFramework($this->mockContaoFramework());

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }

    /**
     * Mocks the application.
     *
     * @return Application
     */
    private function mockApplication()
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

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

use Contao\ManagerBundle\Command\InstallBinDirCommand;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class InstallBinDirCommandTest extends ContaoTestCase
{
    private InstallBinDirCommand $command;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();

        $this->command = new InstallBinDirCommand($this->getTempDir());
        $this->command->setApplication($this->getApplication());
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->getTempDir().'/bin');

        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    public function testNameAndArguments(): void
    {
        $this->assertSame('contao:install-bin-dir', $this->command->getName());
    }

    public function testCommandRegular(): void
    {
        $this->assertFileDoesNotExist($this->getTempDir().'/bin/console');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertFileExists($this->getTempDir().'/bin/console');
    }

    private function getApplication(): Application
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getTempDir());
        $container->set('filesystem', new Filesystem());

        $kernel = $this->createMock(ContaoKernel::class);
        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        $container->set('kernel', $kernel);

        $application = new Application($kernel);
        $application->setCatchExceptions(true);

        return $application;
    }
}

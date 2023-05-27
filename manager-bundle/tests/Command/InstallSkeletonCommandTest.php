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

use Contao\ManagerBundle\Command\InstallSkeletonCommand;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class InstallSkeletonCommandTest extends ContaoTestCase
{
    private InstallSkeletonCommand $command;
    private Filesystem $filesystem;
    private Finder $webFiles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new InstallSkeletonCommand($this->getTempDir());
        $this->command->setApplication($this->getApplication());
        $this->filesystem = new Filesystem();
        $this->webFiles = Finder::create()->files()->in(__DIR__.'/../../skeleton/public');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->getTempDir().'/bin');
        $this->filesystem->remove($this->getTempDir().'/public');

        $this->resetStaticProperties([Terminal::class]);

        parent::tearDown();
    }

    public function testNameAndArguments(): void
    {
        $this->assertSame('skeleton:install', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('web-dir'));

        // Backwards compatibility
        $this->assertSame(['contao:install-web-dir'], $this->command->getAliases());
    }

    public function testCommandRegular(): void
    {
        $this->assertFileDoesNotExist($this->getTempDir().'/bin/console');

        foreach ($this->webFiles as $file) {
            $this->assertFileDoesNotExist($this->getTempDir().'/public/'.$file->getFilename());
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertFileExists($this->getTempDir().'/bin/console');

        foreach ($this->webFiles as $file) {
            $this->assertFileExists($this->getTempDir().'/public/'.$file->getRelativePathname());
        }
    }

    public function testHtaccessIsNotChangedIfRewriteRuleExists(): void
    {
        $existingHtaccess = <<<'EOT'
            <IfModule mod_headers.c>
              RewriteRule ^ %{ENV:BASE}/index.php [L]
            </IfModule>
            EOT;

        $this->filesystem->dumpFile($this->getTempDir().'/public/.htaccess', $existingHtaccess);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertStringEqualsFile($this->getTempDir().'/public/.htaccess', $existingHtaccess);
    }

    public function testHtaccessIsChangedIfRewriteRuleDoesNotExists(): void
    {
        $existingHtaccess = <<<'EOT'
            # Enable PHP 7.2
            AddHandler application/x-httpd-php72 .php
            EOT;

        $this->filesystem->dumpFile($this->getTempDir().'/public/.htaccess', $existingHtaccess);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertStringEqualsFile(
            $this->getTempDir().'/public/.htaccess',
            $existingHtaccess."\n\n".file_get_contents(__DIR__.'/../../skeleton/public/.htaccess')
        );
    }

    public function testCommandRemovesAppDevPhp(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/public/app_dev.php', 'foobar-content');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertFileDoesNotExist($this->getTempDir().'/public/app_dev.php');
    }

    public function testCommandRemovesInstallPhp(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/public/install.php', 'foobar-content');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertFileDoesNotExist($this->getTempDir().'/public/install.php');
    }

    public function testUsesACustomWebDirectory(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['web-dir' => 'web']);

        $this->assertFileExists($this->getTempDir().'/web/index.php');
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

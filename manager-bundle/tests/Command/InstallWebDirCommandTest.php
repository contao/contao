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

use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\ManagerBundle\Command\InstallWebDirCommand;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class InstallWebDirCommandTest extends ContaoTestCase
{
    /**
     * @var InstallWebDirCommand
     */
    private $command;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Finder
     */
    private $webFiles;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->command = new InstallWebDirCommand($this->getTempDir());
        $this->command->setApplication($this->mockApplication());
        $this->filesystem = new Filesystem();
        $this->webFiles = Finder::create()->files()->in(__DIR__.'/../../src/Resources/skeleton/web');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->getTempDir().'/web');
    }

    public function testNameAndArguments(): void
    {
        $this->assertSame('contao:install-web-dir', $this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasArgument('target'));
    }

    public function testCommandRegular(): void
    {
        foreach ($this->webFiles as $file) {
            $this->assertFileNotExists($this->getTempDir().'/web/'.$file->getFilename());
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        foreach ($this->webFiles as $file) {
            $this->assertFileExists($this->getTempDir().'/web/'.$file->getRelativePathname());

            $expectedString = file_get_contents($file->getPathname());
            $expectedString = str_replace(['{root-dir}', '{vendor-dir}'], ['../app', '../vendor'], $expectedString);

            $this->assertStringEqualsFile($this->getTempDir().'/web/'.$file->getRelativePathname(), $expectedString);
        }
    }

    public function testCommandDoesNotOverrideOptionals(): void
    {
        foreach ($this->webFiles as $file) {
            $this->filesystem->dumpFile($this->getTempDir().'/web/'.$file->getRelativePathname(), 'foobar-content');
        }

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        foreach ($this->webFiles as $file) {
            if ('robots.txt' === $file->getRelativePathname()) {
                $this->assertStringEqualsFile($this->getTempDir().'/web/'.$file->getFilename(), 'foobar-content');
            } else {
                $this->assertStringNotEqualsFile($this->getTempDir().'/web/'.$file->getFilename(), 'foobar-content');
            }
        }
    }

    public function testHtaccessIsNotChangedIfRewriteRuleExists(): void
    {
        $existingHtaccess = <<<'EOT'
<IfModule mod_headers.c>
  RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>
EOT;

        $this->filesystem->dumpFile($this->getTempDir().'/web/.htaccess', $existingHtaccess);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertStringEqualsFile($this->getTempDir().'/web/.htaccess', $existingHtaccess);
    }

    public function testHtaccessIsChangedIfRewriteRuleDoesNotExists(): void
    {
        $existingHtaccess = <<<'EOT'
# Enable PHP 7.2
AddHandler application/x-httpd-php72 .php
EOT;

        $this->filesystem->dumpFile($this->getTempDir().'/web/.htaccess', $existingHtaccess);

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertStringEqualsFile(
            $this->getTempDir().'/web/.htaccess',
            $existingHtaccess."\n\n".file_get_contents(__DIR__.'/../../src/Resources/skeleton/web/.htaccess')
        );
    }

    public function testCommandRemovesAppDevPhp(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/web/app_dev.php', 'foobar-content');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertFileNotExists($this->getTempDir().'/web/app_dev.php');
    }

    public function testCommandRemovesInstallPhp(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/web/install.php', 'foobar-content');

        $commandTester = new CommandTester($this->command);
        $commandTester->execute([]);

        $this->assertFileNotExists($this->getTempDir().'/web/install.php');
    }

    public function testUsesACustomTargetDirectory(): void
    {
        $commandTester = new CommandTester($this->command);
        $commandTester->execute(['target' => 'public']);

        $this->assertFileExists($this->getTempDir().'/public/index.php');
    }

    private function mockApplication(ManagerConfig $config = null): Application
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getTempDir());
        $container->set('filesystem', new Filesystem());

        $kernel = $this->createMock(ContaoKernel::class);
        $kernel
            ->method('getContainer')
            ->willReturn($container)
        ;

        if (null !== $config) {
            $kernel
                ->expects($this->atLeastOnce())
                ->method('getManagerConfig')
                ->willReturn($config)
            ;
        }

        $container->set('kernel', $kernel);

        $application = new Application($kernel);
        $application->setCatchExceptions(true);

        return $application;
    }
}

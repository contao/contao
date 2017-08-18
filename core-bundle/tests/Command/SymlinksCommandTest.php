<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Tests the SymlinksCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SymlinksCommandTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $fs = new Filesystem();

        $fs->remove($this->getRootDir().'/system/logs');
        $fs->remove($this->getRootDir().'/system/themes');
        $fs->remove($this->getRootDir().'/var/cache');
        $fs->remove($this->getRootDir().'/web/assets');
        $fs->remove($this->getRootDir().'/web/system');
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $command = new SymlinksCommand('contao:symlinks');

        $this->assertInstanceOf('Contao\CoreBundle\Command\SymlinksCommand', $command);
        $this->assertSame('contao:symlinks', $command->getName());
    }

    /**
     * Tests that the Contao folders are symlinked.
     */
    public function testSymlinksTheContaoFolders()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.logs_dir', $this->getRootDir().'/var/logs');
        $container->setParameter('kernel.project_dir', $this->getRootDir());
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('contao.upload_path', 'app');

        $container->set(
            'contao.resource_finder',
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao')
        );

        $command = new SymlinksCommand('contao:symlinks');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertContains('web/system/modules/foobar/assets', $display);
        $this->assertContains('system/modules/foobar/assets', $display);
        $this->assertContains('web/system/modules/foobar/html', $display);
        $this->assertContains('system/modules/foobar/html', $display);
        $this->assertContains('web/system/modules/foobar/html/foo', $display);
        $this->assertContains('Skipped because system/modules/foobar/html will be symlinked.', $display);
        $this->assertContains('system/themes/flexible', $display);
        $this->assertContains('vendor/contao/test-bundle/Resources/contao/themes/flexible', $display);
        $this->assertContains('web/assets', $display);
        $this->assertContains('assets', $display);
        $this->assertContains('web/system/themes', $display);
        $this->assertContains('system/themes', $display);
        $this->assertContains('system/logs', $display);
        $this->assertContains('var/logs', $display);
    }

    /**
     * Tests that the command is locked while running.
     */
    public function testIsLockedWhileRunning()
    {
        $lock = new LockHandler('contao:symlinks');
        $lock->lock();

        $command = new SymlinksCommand('contao:symlinks');
        $tester = new CommandTester($command);

        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }

    /**
     * Tests that absolute paths are converted to relative paths.
     */
    public function testConvertsAbsolutePathsToRelativePaths()
    {
        $command = new SymlinksCommand('contao:symlinks');

        // Use \ as directory separator in $rootDir
        $rootDir = new \ReflectionProperty(SymlinksCommand::class, 'rootDir');
        $rootDir->setAccessible(true);
        $rootDir->setValue($command, strtr($this->getRootDir(), '/', '\\'));

        // Use / as directory separator in $path
        $method = new \ReflectionMethod(SymlinksCommand::class, 'getRelativePath');
        $method->setAccessible(true);
        $relativePath = $method->invoke($command, $this->getRootDir().'/var/logs');

        // The path should be normalized and shortened
        $this->assertSame('var/logs', $relativePath);
    }
}

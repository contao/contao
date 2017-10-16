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

use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class SymlinksCommandTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();

        $fs->remove($this->getRootDir().'/system/logs');
        $fs->remove($this->getRootDir().'/system/themes');
        $fs->remove($this->getRootDir().'/var/cache');
        $fs->remove($this->getRootDir().'/web/assets');
        $fs->remove($this->getRootDir().'/web/system');
    }

    public function testCanBeInstantiated(): void
    {
        $command = new SymlinksCommand('contao:symlinks');

        $this->assertInstanceOf('Contao\CoreBundle\Command\SymlinksCommand', $command);
        $this->assertSame('contao:symlinks', $command->getName());
    }

    public function testSymlinksTheContaoFolders(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.logs_dir', $this->getRootDir().'/var/logs');
        $container->setParameter('kernel.project_dir', $this->getRootDir());
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('contao.upload_path', 'app');
        $container->set('filesystem', new Filesystem());

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

    public function testIsLockedWhileRunning(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', 'foobar');

        $factory = new Factory(new FlockStore(sys_get_temp_dir().'/'.md5('foobar')));

        $lock = $factory->createLock('contao:symlinks');
        $lock->acquire();

        $command = new SymlinksCommand('contao:symlinks');
        $command->setContainer($container);

        $tester = new CommandTester($command);

        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }

    public function testConvertsAbsolutePathsToRelativePaths(): void
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

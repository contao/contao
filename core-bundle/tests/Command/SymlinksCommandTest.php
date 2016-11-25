<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Test\TestCase;
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
        $fs->remove($this->getRootDir().'/web');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new SymlinksCommand('contao:symlinks');

        $this->assertInstanceOf('Contao\CoreBundle\Command\SymlinksCommand', $command);
        $this->assertEquals('contao:symlinks', $command->getName());
    }

    /**
     * Tests the output.
     */
    public function testOutput()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('kernel.logs_dir', $this->getRootDir().'/var/logs');
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

        $this->assertEquals(0, $code);
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
     * Tests the lock.
     */
    public function testLock()
    {
        $lock = new LockHandler('contao:symlinks');
        $lock->lock();

        $command = new SymlinksCommand('contao:symlinks');
        $tester = new CommandTester($command);

        $code = $tester->execute([]);

        $this->assertEquals(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }
}

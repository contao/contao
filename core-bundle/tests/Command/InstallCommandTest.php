<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Command;

use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Tests the InstallCommand class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallCommandTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $fs = new Filesystem();

        $fs->remove($this->getRootDir().'/assets/css');
        $fs->remove($this->getRootDir().'/assets/images');
        $fs->remove($this->getRootDir().'/assets/images_test');
        $fs->remove($this->getRootDir().'/assets/js');
        $fs->remove($this->getRootDir().'/files');
        $fs->remove($this->getRootDir().'/files_test');
        $fs->remove($this->getRootDir().'/system/cache');
        $fs->remove($this->getRootDir().'/system/config');
        $fs->remove($this->getRootDir().'/system/initialize.php');
        $fs->remove($this->getRootDir().'/system/modules/.gitignore');
        $fs->remove($this->getRootDir().'/system/tmp');
        $fs->remove($this->getRootDir().'/templates');
        $fs->remove($this->getRootDir().'/web');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new InstallCommand('contao:install');

        $this->assertInstanceOf('Contao\CoreBundle\Command\InstallCommand', $command);
        $this->assertEquals('contao:install', $command->getName());
    }

    /**
     * Tests the installation.
     */
    public function testInstallation()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('contao.upload_path', 'files');
        $container->setParameter('contao.image.target_path', 'assets/images');

        $command = new InstallCommand('contao:install');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertEquals(0, $code);
        $this->assertContains(' * files', $display);
        $this->assertContains(' * templates', $display);
        $this->assertContains(' * web/system', $display);
        $this->assertContains(' * assets/css', $display);
        $this->assertContains(' * assets/images', $display);
        $this->assertContains(' * assets/js', $display);
        $this->assertContains(' * system/cache', $display);
        $this->assertContains(' * system/config', $display);
        $this->assertContains(' * system/tmp', $display);
    }

    /**
     * Tests the installation with a custom files and images directory.
     */
    public function testInstallationWithCustomPaths()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('contao.upload_path', 'files_test');
        $container->setParameter('contao.image.target_path', 'assets/images_test');

        $command = new InstallCommand('contao:install');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertEquals(0, $code);
        $this->assertContains(' * files_test', $display);
        $this->assertContains(' * assets/images_test', $display);
    }

    /**
     * Tests the lock.
     */
    public function testLock()
    {
        $lock = new LockHandler('contao:install');
        $lock->lock();

        $command = new InstallCommand('contao:install');
        $tester = new CommandTester($command);

        $code = $tester->execute([]);

        $this->assertEquals(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }
}

<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Tests\TestCase;
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
        $fs->remove($this->getRootDir().'/files_test');
        $fs->remove($this->getRootDir().'/system/cache');
        $fs->remove($this->getRootDir().'/system/config');
        $fs->remove($this->getRootDir().'/system/initialize.php');
        $fs->remove($this->getRootDir().'/system/modules/.gitignore');
        $fs->remove($this->getRootDir().'/system/tmp');
        $fs->remove($this->getRootDir().'/templates');
        $fs->remove($this->getRootDir().'/web/share');
        $fs->remove($this->getRootDir().'/web/system');
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $command = new InstallCommand('contao:install');

        $this->assertInstanceOf('Contao\CoreBundle\Command\InstallCommand', $command);
        $this->assertSame('contao:install', $command->getName());
    }

    /**
     * Tests creating the the Contao folders.
     */
    public function testCreatesTheContaoFolders()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getRootDir());
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('contao.upload_path', 'files');
        $container->setParameter('contao.image.target_dir', $this->getRootDir().'/assets/images');

        $command = new InstallCommand('contao:install');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertContains(' * templates', $output);
        $this->assertContains(' * web/system', $output);
        $this->assertContains(' * assets/css', $output);
        $this->assertContains(' * assets/images', $output);
        $this->assertContains(' * assets/js', $output);
        $this->assertContains(' * system/cache', $output);
        $this->assertContains(' * system/config', $output);
        $this->assertContains(' * system/tmp', $output);
    }

    /**
     * Tests adding a custom files and images directory.
     */
    public function testHandlesCustomFilesAndImagesPaths()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getRootDir());
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('contao.upload_path', 'files_test');
        $container->setParameter('contao.image.target_dir', $this->getRootDir().'/assets/images_test');

        $command = new InstallCommand('contao:install');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertContains(' * files_test', $display);
        $this->assertContains(' * assets/images_test', $display);
    }

    /**
     * Tests that the command is locked while running.
     */
    public function testIsLockedWhileRunning()
    {
        $lock = new LockHandler('contao:install');
        $lock->lock();

        $command = new InstallCommand('contao:install');
        $tester = new CommandTester($command);

        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }
}

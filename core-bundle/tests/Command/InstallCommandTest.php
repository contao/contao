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

use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

class InstallCommandTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $tcpdfPath = $this->getRootDir().'/vendor/contao/core-bundle/src/Resources/contao/config/tcpdf.php';

        if (!file_exists($tcpdfPath)) {
            if (!file_exists(\dirname($tcpdfPath))) {
                mkdir(\dirname($tcpdfPath), 0777, true);
            }

            file_put_contents($tcpdfPath, '');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();

        $fs->remove($this->getRootDir().'/assets/css');
        $fs->remove($this->getRootDir().'/assets/images');
        $fs->remove($this->getRootDir().'/assets/images_test');
        $fs->remove($this->getRootDir().'/assets/js');
        $fs->remove($this->getRootDir().'/files_test');
        $fs->remove($this->getRootDir().'/system/cache');
        $fs->remove($this->getRootDir().'/system/config');
        $fs->remove($this->getRootDir().'/system/modules/.gitignore');
        $fs->remove($this->getRootDir().'/system/tmp');
        $fs->remove($this->getRootDir().'/templates');
        $fs->remove($this->getRootDir().'/web/share');
        $fs->remove($this->getRootDir().'/web/system');
        $fs->remove($this->getRootDir().'/vendor/contao/core-bundle/src/Resources/contao/config/tcpdf.php');
    }

    public function testCanBeInstantiated(): void
    {
        $command = new InstallCommand('contao:install');

        $this->assertInstanceOf('Contao\CoreBundle\Command\InstallCommand', $command);
        $this->assertSame('contao:install', $command->getName());
    }

    public function testCreatesTheContaoFolders(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getRootDir());
        $container->setParameter('kernel.root_dir', $this->getRootDir().'/app');
        $container->setParameter('contao.upload_path', 'files');
        $container->setParameter('contao.image.target_dir', $this->getRootDir().'/assets/images');
        $container->set('filesystem', new Filesystem());

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

    public function testHandlesCustomFilesAndImagesPaths(): void
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

    public function testIsLockedWhileRunning(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', 'foobar');

        $factory = new Factory(new FlockStore(sys_get_temp_dir().'/'.md5('foobar')));

        $lock = $factory->createLock('contao:install');
        $lock->acquire();

        $command = new InstallCommand('contao:install');
        $command->setContainer($container);

        $tester = new CommandTester($command);

        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }
}

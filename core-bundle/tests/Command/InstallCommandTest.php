<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
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

        $fs->remove($this->getRootDir() . '/assets');
        $fs->remove($this->getRootDir() . '/files');
        $fs->remove($this->getRootDir() . '/system/cache');
        $fs->remove($this->getRootDir() . '/system/config');
        $fs->remove($this->getRootDir() . '/system/logs');
        $fs->remove($this->getRootDir() . '/system/themes');
        $fs->remove($this->getRootDir() . '/system/tmp');
        $fs->remove($this->getRootDir() . '/templates');
        $fs->remove($this->getRootDir() . '/web');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new InstallCommand('contao:install');

        $this->assertInstanceOf('Contao\\CoreBundle\\Command\\InstallCommand', $command);
    }

    /**
     * Tests the output.
     */
    public function testOutput()
    {
        $command = new InstallCommand('contao:install');
        $tester  = new CommandTester($command);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getRootDir() . '/app');
        $command->setContainer($container);

        $code = $tester->execute([]);

        $this->assertEquals(0, $code);
        $this->assertContains('Created the ' . $this->getRootDir() . '/files directory.', $tester->getDisplay());
        $this->assertContains('Created the ' . $this->getRootDir() . '/templates directory.', $tester->getDisplay());
        $this->assertContains('Created the ' . $this->getRootDir() . '/web/system directory.', $tester->getDisplay());
        $this->assertContains('Added the ' . $this->getRootDir() . '/assets/css/.gitignore file.', $tester->getDisplay());
        $this->assertContains('Added the ' . $this->getRootDir() . '/assets/images/.gitignore file.', $tester->getDisplay());
        $this->assertContains('Added the ' . $this->getRootDir() . '/assets/js/.gitignore file.', $tester->getDisplay());
        $this->assertContains('Added the ' . $this->getRootDir() . '/system/cache/.gitignore file.', $tester->getDisplay());
        $this->assertContains('Added the ' . $this->getRootDir() . '/system/config/.gitignore file.', $tester->getDisplay());
        $this->assertContains('Added the ' . $this->getRootDir() . '/system/logs/.gitignore file.', $tester->getDisplay());
        $this->assertContains('Added the ' . $this->getRootDir() . '/system/themes/.gitignore file.', $tester->getDisplay());
        $this->assertContains('Added the ' . $this->getRootDir() . '/system/tmp/.gitignore file.', $tester->getDisplay());
    }

    /**
     * Tests the lock.
     */
    public function testLock()
    {
        $lock = new LockHandler('contao:install');
        $lock->lock();

        $command = new InstallCommand('contao:install');
        $tester  = new CommandTester($command);

        $code = $tester->execute([]);

        $this->assertEquals(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());

        $lock->release();
    }
}

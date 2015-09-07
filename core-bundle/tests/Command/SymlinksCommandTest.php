<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
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

        $fs->remove($this->getRootDir() . '/system/logs');
        $fs->remove($this->getRootDir() . '/web');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $command = new SymlinksCommand('contao:symlinks');

        $this->assertInstanceOf('Contao\\CoreBundle\\Command\\SymlinksCommand', $command);
    }

    /**
     * Tests the output.
     */
    public function testOutput()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getRootDir() . '/app');
        $container->setParameter('contao.upload_path', 'app');

        $container->set(
            'contao.resource_finder',
            new ResourceFinder($this->getRootDir() . '/vendor/contao/test-bundle/Resources/contao')
        );

        $command = new SymlinksCommand('contao:symlinks');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $expected = <<<EOF
Added web/system/modules/foobar/assets as symlink to system/modules/foobar/assets.
Added web/system/modules/foobar/html as symlink to system/modules/foobar/html.
Skipped system/modules/foobar/html/foo because system/modules/foobar/html has been symlinked already.
Added system/themes/flexible as symlink to vendor/contao/test-bundle/Resources/contao/themes/flexible.
Added web/assets as symlink to assets.
Added web/system/themes as symlink to system/themes.
Added system/logs as symlink to app/logs.\n
EOF;

        $this->assertEquals(0, $code);
        $this->assertEquals($expected, str_replace("\r", '', $tester->getDisplay()));
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

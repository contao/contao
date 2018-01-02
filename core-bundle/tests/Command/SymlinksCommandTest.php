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

        $fs->remove($this->getFixturesDir().'/system/logs');
        $fs->remove($this->getFixturesDir().'/system/themes');
        $fs->remove($this->getFixturesDir().'/var/cache');
        $fs->remove($this->getFixturesDir().'/web/assets');
        $fs->remove($this->getFixturesDir().'/web/system');
        $fs->remove($this->getFixturesDir().'/system/config/tcpdf.php');
    }

    public function testCanBeInstantiated(): void
    {
        $command = new SymlinksCommand('contao:symlinks');

        $this->assertInstanceOf('Contao\CoreBundle\Command\SymlinksCommand', $command);
        $this->assertSame('contao:symlinks', $command->getName());
    }

    public function testSymlinksTheContaoFolders(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getFixturesDir().'/system/themes/default');

        $finder = new ResourceFinder($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao');

        $container = $this->mockContainer($this->getFixturesDir());
        $container->setParameter('kernel.logs_dir', $this->getFixturesDir().'/var/logs');
        $container->set('contao.resource_finder', $finder);

        $command = new SymlinksCommand('contao:symlinks');
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(1, $code);
        $this->assertContains(' web/system/modules/foobar/html/foo/bar ', $display);
        $this->assertContains(' Skipped because system/modules/foobar/html will be symlinked. ', $display);
        $this->assertContains(' web/system/modules/foobar/assets ', $display);
        $this->assertContains(' system/modules/foobar/assets ', $display);
        $this->assertContains(' web/system/modules/foobar/html ', $display);
        $this->assertContains(' system/modules/foobar/html ', $display);
        $this->assertContains(' system/themes/default ', $display);
        $this->assertContains(' The path "system/themes/default" exists and is not a symlink. ', $display);
        $this->assertContains(' system/themes/flexible ', $display);
        $this->assertContains(' vendor/contao/test-bundle/Resources/contao/themes/flexible ', $display);
        $this->assertContains(' web/assets ', $display);
        $this->assertContains(' assets ', $display);
        $this->assertContains(' web/system/themes ', $display);
        $this->assertContains(' system/themes ', $display);
        $this->assertContains(' system/logs ', $display);
        $this->assertContains(' var/logs ', $display);
        $this->assertContains(' system/config/tcpdf.php ', $display);
        $this->assertContains(' vendor/contao/core-bundle/src/Resources/contao/config/tcpdf.php ', $display);
    }

    public function testIsLockedWhileRunning(): void
    {
        $factory = new Factory(new FlockStore(sys_get_temp_dir().'/'.md5($this->getFixturesDir())));

        $lock = $factory->createLock('contao:symlinks');
        $lock->acquire();

        $command = new SymlinksCommand('contao:symlinks');
        $command->setContainer($this->mockContainer($this->getFixturesDir()));

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
        $rootDir->setValue($command, strtr($this->getFixturesDir(), '/', '\\'));

        // Use / as directory separator in $path
        $method = new \ReflectionMethod(SymlinksCommand::class, 'getRelativePath');
        $method->setAccessible(true);
        $relativePath = $method->invoke($command, $this->getFixturesDir().'/var/logs');

        // The path should be normalized and shortened
        $this->assertSame('var/logs', $relativePath);
    }
}

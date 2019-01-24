<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Command;

use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\LockInterface;

class SymlinksCommandTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove($this->getFixturesDir().'/system/config');
        $fs->remove($this->getFixturesDir().'/system/logs');
        $fs->remove($this->getFixturesDir().'/system/themes');
        $fs->remove($this->getFixturesDir().'/var');
        $fs->remove($this->getFixturesDir().'/web');
    }

    public function testSymlinksTheContaoFolders(): void
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getFixturesDir().'/system/themes/default');
        $fs->mkdir($this->getFixturesDir().'/var/logs');

        $command = $this->mockCommand();
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
    }

    public function testIsLockedWhileRunning(): void
    {
        $command = $this->mockCommand(true);
        $tester = new CommandTester($command);
        $code = $tester->execute([]);

        $this->assertSame(1, $code);
        $this->assertContains('The command is already running in another process.', $tester->getDisplay());
    }

    public function testConvertsAbsolutePathsToRelativePaths(): void
    {
        $command = (new \ReflectionClass(SymlinksCommand::class))->newInstanceWithoutConstructor();

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

    private function mockCommand(bool $isLocked = false): SymlinksCommand
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->expects($this->once())
            ->method('acquire')
            ->willReturn(!$isLocked)
        ;

        $lock
            ->expects($isLocked ? $this->never() : $this->once())
            ->method('release')
        ;

        return new SymlinksCommand(
            $this->getFixturesDir(),
            'files',
            $this->getFixturesDir().'/var/logs',
            new ResourceFinder($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao'),
            $this->createMock(EventDispatcherInterface::class),
            $lock
        );
    }
}

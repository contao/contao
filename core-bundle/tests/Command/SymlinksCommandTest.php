<?php

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
use Symfony\Component\Filesystem\Filesystem;

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
    protected function tearDown()
    {
        $fs = new Filesystem();

        $fs->remove($this->getRootDir().'/system/logs');
        $fs->remove($this->getRootDir().'/system/themes');
        $fs->remove($this->getRootDir().'/var');
        $fs->remove($this->getRootDir().'/web');
    }

    /**
     * Tests symlinking the Contao folders.
     */
    public function testSymlinksTheContaoFolders()
    {
        $fs = new Filesystem();
        $fs->mkdir($this->getRootDir().'/var/logs');

        $command = new SymlinksCommand(
            $this->getRootDir(),
            'files',
            $this->getRootDir().'/var/logs',
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao')
        );

        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);
        $this->assertNotRegExp('# web/files +files #', $display);
        $this->assertRegExp('# web/files/public +files/public #', $display);
        $this->assertRegExp('# web/system/modules/foobar/html/foo/bar +Skipped because system/modules/foobar/html will be symlinked\. #', $display);
        $this->assertRegExp('# web/system/modules/foobar/assets +system/modules/foobar/assets #', $display);
        $this->assertRegExp('# web/system/modules/foobar/html +system/modules/foobar/html #', $display);
        $this->assertRegExp('# system/themes/flexible +vendor/contao/test-bundle/Resources/contao/themes/flexible #', $display);
        $this->assertRegExp('# web/assets +assets #', $display);
        $this->assertRegExp('# web/system/themes +system/themes #', $display);
        $this->assertRegExp('# system/logs +var/logs #', $display);
    }

    /**
     * Tests that absolute paths are converted to relative paths.
     */
    public function testConvertsAbsolutePathsToRelativePaths()
    {
        $command = new SymlinksCommand(
            $this->getRootDir(),
            'files',
            $this->getRootDir().'/var/logs',
            new ResourceFinder($this->getRootDir().'/vendor/contao/test-bundle/Resources/contao')
        );

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

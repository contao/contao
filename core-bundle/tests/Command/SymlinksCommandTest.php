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
use Webmozart\PathUtil\Path;

class SymlinksCommandTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $filesystem = new Filesystem();

        foreach (['assets', 'files', 'system', 'var', 'vendor'] as $directory) {
            $filesystem->mirror(
                Path::join(__DIR__.'/../Fixtures', $directory),
                Path::join(self::getTempDir(), $directory)
            );
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove([
            Path::join($this->getTempDir(), 'system/config'),
            Path::join($this->getTempDir(), 'system/logs'),
            Path::join($this->getTempDir(), 'system/themes'),
            Path::join($this->getTempDir(), 'var'),
            Path::join($this->getTempDir(), 'web'),
        ]);
    }

    public function testSymlinksTheContaoFolders(): void
    {
        $command = $this->getCommand();
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);

        $this->assertNotRegExp('# web/files +files #', $display);
        $this->assertRegExp('# web/files/public +files/public #', $display);
        $this->assertRegExp('# web/system/modules/foobar/html/foo/bar +Skipped because system/modules/foobar/html will be symlinked\. #', $display);
        $this->assertRegExp('# web/system/modules/foobar/assets +system/modules/foobar/assets #', $display);
        $this->assertRegExp('# web/system/modules/foobar/html +system/modules/foobar/html #', $display);
        $this->assertRegExp('# vendor/contao/test-bundle/Resources/contao/themes/default #', $display);
        $this->assertRegExp('# system/themes/flexible +vendor/contao/test-bundle/Resources/contao/themes/flexible #', $display);
        $this->assertRegExp('# web/assets +assets #', $display);
        $this->assertRegExp('# web/system/themes +system/themes #', $display);
        $this->assertRegExp('# system/logs +var/logs #', $display);

        $this->assertFileExists(Path::join(self::getTempDir(), 'web/files/public'));
        $this->assertDirectoryExists(Path::join(self::getTempDir(), 'web/system/modules/foobar'));
        $this->assertDirectoryExists(Path::join(self::getTempDir(), 'web/system/themes/default'));
        $this->assertDirectoryExists(Path::join(self::getTempDir(), 'web/assets'));
    }

    public function testConvertsAbsolutePathsToRelativePaths(): void
    {
        $command = (new \ReflectionClass(SymlinksCommand::class))->newInstanceWithoutConstructor();

        // Use \ as directory separator in $projectDir
        $projectDir = new \ReflectionProperty(SymlinksCommand::class, 'projectDir');
        $projectDir->setAccessible(true);
        $projectDir->setValue($command, strtr($this->getTempDir(), '/', '\\'));

        // Use / as directory separator in $path
        $method = new \ReflectionMethod(SymlinksCommand::class, 'getRelativePath');
        $method->setAccessible(true);
        $relativePath = $method->invoke($command, Path::join($this->getTempDir(), 'var/logs'));

        // The path should be normalized and shortened
        $this->assertSame('var/logs', $relativePath);
    }

    private function getCommand(): SymlinksCommand
    {
        return new SymlinksCommand(
            $this->getTempDir(),
            'files',
            Path::join($this->getTempDir(), '/var/logs'),
            new ResourceFinder(Path::join($this->getTempDir(), 'vendor/contao/test-bundle/Resources/contao')),
            $this->createMock(EventDispatcherInterface::class)
        );
    }
}

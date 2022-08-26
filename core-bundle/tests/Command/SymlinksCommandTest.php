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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class SymlinksCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
        (new Filesystem())->remove([
            Path::join($this->getTempDir(), 'public'),
            Path::join($this->getTempDir(), 'system/config'),
            Path::join($this->getTempDir(), 'system/logs'),
            Path::join($this->getTempDir(), 'system/themes'),
            Path::join($this->getTempDir(), 'var'),
        ]);

        $this->resetStaticProperties([Table::class, Terminal::class]);

        parent::tearDown();
    }

    public function testSymlinksTheContaoFolders(): void
    {
        $command = $this->getCommand();
        $tester = new CommandTester($command);
        $code = $tester->execute([]);
        $display = $tester->getDisplay();

        $this->assertSame(0, $code);

        $this->assertDoesNotMatchRegularExpression('# public/files +files #', $display);
        $this->assertMatchesRegularExpression('# public/files/public +files/public #', $display);
        $this->assertMatchesRegularExpression('# public/system/modules/foobar/html/foo/bar +Skipped because system/modules/foobar/html will be symlinked\. #', $display);
        $this->assertMatchesRegularExpression('# public/system/modules/foobar/assets +system/modules/foobar/assets #', $display);
        $this->assertMatchesRegularExpression('# public/system/modules/foobar/html +system/modules/foobar/html #', $display);
        $this->assertMatchesRegularExpression('# vendor/contao/test-bundle/Resources/contao/themes/default #', $display);
        $this->assertMatchesRegularExpression('# system/themes/flexible +vendor/contao/test-bundle/Resources/contao/themes/flexible #', $display);
        $this->assertMatchesRegularExpression('# public/assets +assets #', $display);
        $this->assertMatchesRegularExpression('# public/system/themes +system/themes #', $display);
        $this->assertMatchesRegularExpression('# system/logs +var/logs #', $display);

        $this->assertFileExists(Path::join(self::getTempDir(), 'public/files/public'));
        $this->assertDirectoryExists(Path::join(self::getTempDir(), 'public/system/modules/foobar'));
        $this->assertDirectoryExists(Path::join(self::getTempDir(), 'public/system/themes/default'));
        $this->assertDirectoryExists(Path::join(self::getTempDir(), 'public/assets'));
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

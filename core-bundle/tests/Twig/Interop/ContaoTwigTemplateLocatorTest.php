<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Interop;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContaoTwigTemplateLocator;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ContaoTwigTemplateLocatorTest extends TestCase
{
    public function testDiscoversAppTemplateDirectory(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->with('/default/path/templates/contao')
            ->willReturn(true)
        ;

        $locator = new ContaoTwigTemplateLocator($filesystem);

        $this->assertSame(
            '/default/path/templates/contao',
            $locator->getAppPath('/default/path/templates')
        );
    }

    public function testIgnoresMissingAppTemplateDirectory(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->with('/default/path/templates/contao')
            ->willReturn(false)
        ;

        $locator = new ContaoTwigTemplateLocator($filesystem);

        $this->assertNull($locator->getAppPath('/default/path/templates'));
    }

    public function testDiscoversAppThemeTemplateDirectories(): void
    {
        $defaultPath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/templates');

        $locator = new ContaoTwigTemplateLocator();

        $this->assertSame(
            [
                'foo-theme' => Path::join($defaultPath, 'contao/foo-theme'),
            ],
            $locator->getAppThemePaths($defaultPath)
        );
    }

    public function testDiscoversBundleTemplateDirectories(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->willReturnCallback(
                static function (string $path) {
                    $validPaths = [
                        '/path/to/foo/Resources/views/contao',
                        '/path/to/bar/templates/contao',
                    ];

                    return \in_array($path, $validPaths, true);
                }
            )
        ;

        $locator = new ContaoTwigTemplateLocator($filesystem);

        $paths = $locator->getBundlePaths(
            [
                '@FooBundle' => ['path' => '/path/to/foo'],
                '@BarBundle' => ['path' => '/path/to/bar'],
                '@BazBundle' => ['path' => '/path/to/baz'],
            ]
        );

        $this->assertSame([
            '@FooBundle' => '/path/to/foo/Resources/views/contao',
            '@BarBundle' => '/path/to/bar/templates/contao',
        ], $paths);
    }
}

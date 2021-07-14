<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Loader;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Webmozart\PathUtil\Path;

class TemplateLocatorTest extends TestCase
{
    public function testFindsThemeDirectories(): void
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');
        $locator = new TemplateLocator($projectDir, [], []);

        $expectedThemeDirectories = [
            'my' => Path::join($projectDir, 'templates/my'),
            'my_theme' => Path::join($projectDir, 'templates/my/theme'),
        ];

        $this->assertSame($expectedThemeDirectories, $locator->findThemeDirectories());
    }

    public function testIgnoresThemeDirectoriesIfPathDoesNotExist(): void
    {
        $locator = new TemplateLocator('/invalid/path', [], []);

        $this->assertEmpty($locator->findThemeDirectories());
    }

    public function testFindsResourcesPaths(): void
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $bundles = [
            'FooBundle' => ContaoModuleBundle::class,
            'BarBundle' => 'class',
            'CoreBundle' => 'class',
        ];

        $bundleMetadata = [
            'FooBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/FooBundle')],
            'BarBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/BarBundle')],
            'CoreBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/CoreBundle')],
        ];

        $locator = new TemplateLocator($projectDir, $bundles, $bundleMetadata);

        $expectedResourcePaths = [
            'App' => [
                Path::join($projectDir, 'contao/templates'),
                Path::join($projectDir, 'contao/templates/some'),
                Path::join($projectDir, 'contao/templates/some/random'),
                Path::join($projectDir, 'src/Resources/contao/templates'),
                Path::join($projectDir, 'app/Resources/contao/templates'),
            ],
            'CoreBundle' => [
                Path::join($projectDir, 'vendor-bundles/CoreBundle/Resources/contao/templates'),
            ],
            'BarBundle' => [
                Path::join($projectDir, 'vendor-bundles/BarBundle/contao/templates'),
            ],
            'FooBundle' => [
                Path::join($projectDir, 'vendor-bundles/FooBundle/templates'),
                Path::join($projectDir, 'vendor-bundles/FooBundle/templates/any'),
            ],
        ];

        $paths = $locator->findResourcesPaths();

        // Make sure the order is like specified
        $this->assertSame(array_keys($expectedResourcePaths), array_keys($paths));
        $this->assertSame(array_values($expectedResourcePaths), array_values($paths));
    }

    public function testFindsTemplates(): void
    {
        $path = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/vendor-bundles/InvalidBundle/templates');
        $locator = new TemplateLocator('/project/dir', [], []);

        $expectedTemplates = [
            'foo.html.twig' => Path::join($path, 'foo.html.twig'),
        ];

        $this->assertSame($expectedTemplates, $locator->findTemplates($path));
    }

    public function testFindsNoTemplatesIfPathDoesNotExist(): void
    {
        $locator = new TemplateLocator('/project/dir', [], []);

        $this->assertEmpty($locator->findTemplates('/invalid/path'));
    }
}

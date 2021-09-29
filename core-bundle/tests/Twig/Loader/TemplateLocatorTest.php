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
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\Model\Collection;
use Contao\ThemeModel;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Webmozart\PathUtil\Path;

class TemplateLocatorTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testFindsThemeDirectories(): void
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $locator = $this->getTemplateLocator(
            $projectDir,
            [
                'templates/my/theme',
                'themes/foo',
                'templates/non-existing',
            ]
        );

        $expectedThemeDirectories = [
            'my_theme' => Path::join($projectDir, 'templates/my/theme'),
            '_themes_foo' => Path::join($projectDir, 'themes/foo'),
        ];

        $this->assertSame($expectedThemeDirectories, $locator->findThemeDirectories());
    }

    /**
     * @group legacy
     */
    public function testTriggersDeprecationIfThemeDirectoryContainsInvalidCharacters(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.12: Using a theme path with invalid characters has been deprecated and will throw an exception in Contao 5.0.');

        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');
        $locator = $this->getTemplateLocator($projectDir, ['themes/invalid.theme']);

        $this->assertEmpty($locator->findThemeDirectories());
    }

    public function testIgnoresMissingThemeTable(): void
    {
        $themeAdapter = $this->mockAdapter(['findAll']);
        $themeAdapter
            ->method('findAll')
            ->willThrowException(
                new TableNotFoundException(
                    'Table tl_theme doesn\'t exist.',
                    $this->createMock(DriverException::class)
                )
            )
        ;

        $framework = $this->mockContaoFramework([ThemeModel::class => $themeAdapter]);

        $locator = new TemplateLocator(
            '',
            [],
            [],
            $this->createMock(ThemeNamespace::class),
            $framework
        );

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

        $locator = $this->getTemplateLocator($projectDir, [], $bundles, $bundleMetadata);

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
        $locator = $this->getTemplateLocator('/project/dir');

        $expectedTemplates = [
            'foo.html.twig' => Path::join($path, 'foo.html.twig'),
        ];

        $this->assertSame($expectedTemplates, $locator->findTemplates($path));
    }

    public function testFindsNoTemplatesIfPathDoesNotExist(): void
    {
        $locator = $this->getTemplateLocator('/project/dir');

        $this->assertEmpty($locator->findTemplates('/invalid/path'));
    }

    private function getTemplateLocator(string $projectDir = '/', array $themePaths = [], array $bundles = [], array $bundlesMetadata = []): TemplateLocator
    {
        $themeModels = array_map(
            function (string $path) {
                return $this->mockClassWithProperties(ThemeModel::class, [
                    'templates' => $path,
                ]);
            },
            $themePaths
        );

        $themeAdapter = $this->mockAdapter(['findAll']);
        $themeAdapter
            ->method('findAll')
            ->willReturn(empty($themePaths) ? null : new Collection($themeModels, 'tl_theme'))
        ;

        return new TemplateLocator(
            $projectDir,
            $bundles,
            $bundlesMetadata,
            new ThemeNamespace(),
            $this->mockContaoFramework([ThemeModel::class => $themeAdapter])
        );
    }
}

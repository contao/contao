<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inheritance;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\Model\Collection;
use Contao\ThemeModel;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Environment;
use Webmozart\PathUtil\Path;

/**
 * Integration tests.
 */
class InheritanceTest extends TestCase
{
    public function testInheritsMultipleTimes(): void
    {
        $environment = $this->getDemoEnvironment();

        $html = $environment->render(
            '@Contao/text.html.twig',
            ['content' => 'This &amp; that']
        );

        // Global > App > BarBundle > FooBundle > CoreBundle
        $expected = '<global><app><bar><foo>Content: This &amp; that</foo></bar></app></global>';

        $this->assertSame($expected, $html);
    }

    public function testInheritsMultipleTimesWithTheme(): void
    {
        $environment = $this->getDemoEnvironment();

        $page = new \stdClass();
        $page->templateGroup = 'templates/my/theme';

        $GLOBALS['objPage'] = $page;

        $html = $environment->render(
            '@Contao/text.html.twig',
            ['content' => 'This &amp; that']
        );

        // Theme > Global > App > BarBundle > FooBundle > CoreBundle
        $expected = '<theme><global><app><bar><foo>Content: This &amp; that</foo></bar></app></global></theme>';

        $this->assertSame($expected, $html);

        unset($GLOBALS['objPage']);
    }

    public function testThrowsIfTemplatesOfSameTypeAreAmbiguous(): void
    {
        $bundlePath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/vendor-bundles/InvalidBundle');

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage("There cannot be more than one 'foo.html.twig' template in '$bundlePath/templates'.");

        $this->getDemoEnvironment(['InvalidBundle' => ['path' => $bundlePath]]);
    }

    private function getDemoEnvironment(array $bundlesMetadata = null): Environment
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $bundlesMetadata ??= [
            'CoreBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/CoreBundle')],
            'FooBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/FooBundle')],
            'BarBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/BarBundle')],
            'App' => ['path' => Path::join($projectDir, 'contao')],
        ];

        $bundles = array_combine(
            array_keys($bundlesMetadata),
            array_fill(0, \count($bundlesMetadata), ContaoModuleBundle::class)
        );

        $themeAdapter = $this->mockAdapter(['findAll']);
        $themeAdapter
            ->method('findAll')
            ->willReturn(
                new Collection(
                    [$this->mockClassWithProperties(ThemeModel::class, ['templates' => 'templates/my/theme'])],
                    'tl_theme'
                )
            )
        ;

        $framework = $this->mockContaoFramework([ThemeModel::class => $themeAdapter]);
        $themeNamespace = new ThemeNamespace();

        $templateLocator = new TemplateLocator($projectDir, $bundles, $bundlesMetadata, $themeNamespace, $framework);
        $loader = new ContaoFilesystemLoader(new NullAdapter(), $templateLocator, $themeNamespace, $projectDir);

        $warmer = new ContaoFilesystemLoaderWarmer($loader, $templateLocator, $projectDir, 'prod');
        $warmer->warmUp('');

        $environment = new Environment($loader);

        $contaoExtension = new ContaoExtension($environment, $loader);
        $environment->addExtension($contaoExtension);

        return $environment;
    }
}

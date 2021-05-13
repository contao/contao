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

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
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

    private function getDemoEnvironment(): Environment
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $resourcesPaths = [
            'CoreBundle' => Path::join($projectDir, 'vendor-bundles/CoreBundle'),
            'FooBundle' => Path::join($projectDir, 'vendor-bundles/FooBundle'),
            'BarBundle' => Path::join($projectDir, 'vendor-bundles/BarBundle'),
            'App' => Path::join($projectDir, 'contao'),
        ];

        $templateLocator = new TemplateLocator($projectDir);

        $loader = new ContaoFilesystemLoader(new NullAdapter(), $templateLocator, $projectDir);

        $warmer = new ContaoFilesystemLoaderWarmer($loader, $templateLocator, $resourcesPaths, $projectDir);
        $warmer->warmUp('');

        $environment = new Environment($loader);

        $contaoExtension = new ContaoExtension($environment, $loader);
        $environment->addExtension($contaoExtension);

        return $environment;
    }
}

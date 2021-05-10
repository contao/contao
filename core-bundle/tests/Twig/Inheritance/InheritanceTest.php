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
use Contao\CoreBundle\Twig\Inheritance\ContaoTwigTemplateLocator;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchy;
use Contao\CoreBundle\Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Webmozart\PathUtil\Path;

/**
 * Integration tests for our multi-inheritance model.
 */
class InheritanceTest extends TestCase
{
    public function testExpandsMultiInheritance(): void
    {
        $environment = $this->getDemoEnvironment();

        $html = $environment->render(
            '@Contao/text.html.twig',
            ['headline' => 'This &amp; that', 'body' => 'This is <b>amazing</b>!']
        );

        $expected = <<<'TAG'
    <section>
        <div class="app-headline">This &amp; that!!!</div>
        <div class="app-body">This is <b>amazing</b>!</div>
    </section>

TAG;

        $this->assertSame($expected, $html);
    }

    public function testExpandsMultiInheritanceWithTheme(): void
    {
        $environment = $this->getDemoEnvironment();

        $html = $environment->render(
            '@Contao_App_my-theme/text.html.twig',
            ['headline' => 'This &amp; that', 'body' => 'This is <b>amazing</b>!']
        );

        $expected = <<<'TAG'
    <section>
        <div class="app-headline">This &amp; that!!!</div>
        <div class="app-body">This is <b>amazing</b>!</div>
    </section>

    <footer>by my-theme</footer>
TAG;

        $this->assertSame($expected, $html);
    }

    private function getDemoEnvironment(): Environment
    {
        $myTheme = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/templates/@my-theme');

        $app = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/templates');

        $bundles = [
            Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/bundles/Core'),
            Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/bundles/Foo'),
            Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/bundles/Bar'),
        ];

        $templateHierarchy = new TemplateHierarchy([
            'Core' => ['path' => $bundles[0]],
            'Foo' => ['path' => $bundles[1]],
            'Bar' => ['path' => $bundles[2]],
        ]);

        $templateLocator = new ContaoTwigTemplateLocator();
        $loader = new FilesystemLoader();

        $templateHierarchy->setAppThemeTemplates(
            $templateLocator->findTemplates($myTheme),
            'my-theme'
        );

        $loader->addPath($myTheme, TemplateHierarchy::getAppThemeNamespace('my-theme'));

        $templateHierarchy->setAppTemplates(
            $templateLocator->findTemplates($app)
        );

        $loader->addPath($app, 'Contao');
        $loader->addPath($app, TemplateHierarchy::getAppNamespace());

        $templateHierarchy->setBundleTemplates(
            $templateLocator->findTemplates($bundles[2]),
            'Bar'
        );

        $loader->addPath($bundles[2], 'Contao');
        $loader->addPath($bundles[2], TemplateHierarchy::getBundleNamespace('Bar'));

        $templateHierarchy->setBundleTemplates(
            $templateLocator->findTemplates($bundles[1]),
            'Foo'
        );

        $loader->addPath($bundles[1], 'Contao');
        $loader->addPath($bundles[1], TemplateHierarchy::getBundleNamespace('Foo'));

        $templateHierarchy->setBundleTemplates(
            $templateLocator->findTemplates($bundles[0]),
            'Core'
        );

        $loader->addPath($bundles[0], 'Contao');
        $loader->addPath($bundles[0], TemplateHierarchy::getBundleNamespace('Core'));

        $environment = new Environment($loader);

        $contaoExtension = new ContaoExtension($environment, $templateHierarchy);
        $environment->addExtension($contaoExtension);

        $contaoExtension->registerTemplateForInputEncoding('@Contao/text.html.twig');

        return $environment;
    }
}

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

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Filesystem\Path;
use Twig\Environment;

/**
 * Integration tests.
 */
class InheritanceTest extends TestCase
{
    public function testInheritsMultipleTimes(): void
    {
        $environment = $this->getDemoEnvironment();
        $html = $environment->render('@Contao/text.html.twig', ['content' => 'This &amp; that']);

        // Global > App > BarBundle > FooBundle > CoreBundle
        $expected = '<global><app><bar><foo>Content: This &amp; that</foo></bar></app></global>';

        $this->assertSame($expected, $html);
    }

    public function testInheritsMultipleTimesWithTheme(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->templateGroup = 'templates/my/theme';

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('getCurrentPage')
            ->willReturn($page)
        ;

        $environment = $this->getDemoEnvironment(pageFinder: $pageFinder);
        $html = $environment->render('@Contao/text.html.twig', ['content' => 'This &amp; that']);

        // Theme > Global > App > BarBundle > FooBundle > CoreBundle
        $expected = '<theme><global><app><bar><foo>Content: This &amp; that</foo></bar></app></global></theme>';

        $this->assertSame($expected, $html);
    }

    public function testThrowsIfTemplatesAreAmbiguous(): void
    {
        $bundlePath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/vendor-bundles/InvalidBundle1');

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('There cannot be more than one "foo.html.twig" template in "'.$bundlePath.'/templates".');

        $this->getDemoEnvironment(['InvalidBundle1' => ['path' => $bundlePath]]);
    }

    public function testThrowsIfTemplateTypesAreAmbiguous(): void
    {
        $bundlePath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/vendor-bundles/InvalidBundle2');
        $file1 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/contao/templates/some/random/text.html.twig');
        $file2 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/vendor-bundles/InvalidBundle2/templates/text.json.twig');

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The "text" template has incompatible types, got "html.twig/html5" in "'.$file1.'" and "json.twig" in "'.$file2.'".');

        $this->getDemoEnvironment(['InvalidBundle2' => ['path' => $bundlePath]]);
    }

    private function getDemoEnvironment(array|null $bundlesMetadata = null, PageFinder|null $pageFinder = null): Environment
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $bundlesMetadata ??= [
            'CoreBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/CoreBundle')],
            'FooBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/FooBundle')],
            'BarBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/BarBundle')],
        ];

        $bundles = array_combine(
            array_keys($bundlesMetadata),
            array_fill(0, \count($bundlesMetadata), ContaoModuleBundle::class),
        );

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchFirstColumn')
            ->willReturn(['templates/my/theme'])
        ;

        $themeNamespace = new ThemeNamespace();
        $templateLocator = new TemplateLocator($projectDir, $bundles, $bundlesMetadata, $themeNamespace, $connection);

        $loader = new ContaoFilesystemLoader(
            new NullAdapter(),
            $templateLocator,
            $themeNamespace,
            $this->createMock(ContaoFramework::class),
            $pageFinder ?? $this->createMock(PageFinder::class),
            $projectDir,
        );

        $environment = new Environment($loader);
        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $loader,
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
                new InspectorNodeVisitor(new NullAdapter()),
            ),
        );

        // Make sure errors are thrown (e.g. ambiguous templates)
        $loader->warmUp();

        return $environment;
    }
}

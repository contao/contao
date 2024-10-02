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

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
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
        $environment = $this->getDemoEnvironment();

        $page = new \stdClass();
        $page->templateGroup = 'templates/my/theme';

        $GLOBALS['objPage'] = $page;

        $html = $environment->render('@Contao/text.html.twig', ['content' => 'This &amp; that']);

        // Theme > Global > App > BarBundle > FooBundle > CoreBundle
        $expected = '<theme><global><app><bar><foo>Content: This &amp; that</foo></bar></app></global></theme>';

        $this->assertSame($expected, $html);

        unset($GLOBALS['objPage']);
    }

    public function testThrowsIfTemplatesAreAmbiguous(): void
    {
        $bundlePath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/vendor-bundles/InvalidBundle1/templates');

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('There cannot be more than one "foo.html.twig" template in "'.$bundlePath.'".');

        $this->getDemoEnvironment(['InvalidBundle1' => $bundlePath]);
    }

    public function testThrowsIfTemplateTypesAreAmbiguous(): void
    {
        $bundlePath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/vendor-bundles/InvalidBundle2/templates');
        $file1 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/contao/templates/some/random/text.html.twig');
        $file2 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance/vendor-bundles/InvalidBundle2/templates/text.json.twig');

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The "text" template has incompatible types, got "html.twig/html5" in "'.$file1.'" and "json.twig" in "'.$file2.'".');

        $this->getDemoEnvironment(['InvalidBundle2' => $bundlePath]);
    }

    private function getDemoEnvironment(array|null $paths = null): Environment
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $paths ??= [
            'CoreBundle' => Path::join($projectDir, 'vendor-bundles/CoreBundle/Resources/contao/templates'),
            'foo' => Path::join($projectDir, 'system/modules/foo/templates'),
            'BarBundle' => Path::join($projectDir, 'vendor-bundles/BarBundle/contao/templates'),
        ];

        if (!isset($paths['App'])) {
            $paths['App'] = Path::join($projectDir, 'contao/templates');
        }

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchFirstColumn')
            ->willReturn(['templates/my/theme'])
        ;

        $themeNamespace = new ThemeNamespace();

        $resourceFinder = $this->createMock(ResourceFinder::class);
        $resourceFinder
            ->method('getExistingSubpaths')
            ->with('templates')
            ->willReturn($paths)
        ;

        $templateLocator = new TemplateLocator($projectDir, $resourceFinder, $themeNamespace, $connection);
        $loader = new ContaoFilesystemLoader(new NullAdapter(), $templateLocator, $themeNamespace, $this->createMock(ContaoFramework::class), $projectDir);

        $environment = new Environment($loader);
        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $loader,
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
            ),
        );

        // Make sure errors are thrown (e.g. ambiguous templates)
        $loader->warmUp();

        return $environment;
    }
}

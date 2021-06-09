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
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoaderWarmer;
use Contao\CoreBundle\Twig\Loader\TemplateLocator;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Error\LoaderError;
use Webmozart\PathUtil\Path;

class ContaoFilesystemLoaderTest extends TestCase
{
    public function testAddPath(): void
    {
        $loader = $this->getContaoFilesystemLoader();

        $path1 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/1');
        $path2 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/2');

        $loader->addPath($path1);
        $loader->addPath($path2, 'Contao');
        $loader->addPath($path1, 'Contao_foo-Bar_Baz2');

        $this->assertTrue($loader->exists('@Contao/1.html.twig'));
        $this->assertTrue($loader->exists('@Contao/2.html.twig'));
        $this->assertTrue($loader->exists('@Contao_foo-Bar_Baz2/1.html.twig'));
        $this->assertFalse($loader->exists('@Contao_foo-Bar_Baz2/2.html.twig'));
    }

    public function testPrependPath(): void
    {
        $loader = $this->getContaoFilesystemLoader();

        $path1 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/1');
        $path2 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/2');

        $loader->prependPath($path1);
        $loader->prependPath($path2, 'Contao');
        $loader->prependPath($path1, 'Contao_Foo');

        $this->assertTrue($loader->exists('@Contao/1.html.twig'));
        $this->assertTrue($loader->exists('@Contao/2.html.twig'));
        $this->assertTrue($loader->exists('@Contao_Foo/1.html.twig'));
        $this->assertFalse($loader->exists('@Contao_Foo/2.html.twig'));
    }

    public function testDoesNotAllowToAddNonContaoNamespacedPath(): void
    {
        $loader = $this->getContaoFilesystemLoader();

        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage("Tried to register an invalid Contao namespace 'Foo'.");

        $loader->addPath('foo/path', 'Foo');
    }

    public function testDoesNotAllowToPrependNonContaoNamespacedPath(): void
    {
        $loader = $this->getContaoFilesystemLoader();

        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage("Tried to register an invalid Contao namespace 'Foo'.");

        $loader->prependPath('foo/path', 'Foo');
    }

    public function testToleratesInvalidPaths(): void
    {
        $loader = $this->getContaoFilesystemLoader();

        $loader->addPath('non/existing/path');
        $loader->prependPath('non/existing/path');

        $this->assertEmpty($loader->getPaths());
    }

    public function testClearPaths(): void
    {
        $loader = $this->getContaoFilesystemLoader();

        $loader->addPath(Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/1'));

        $this->assertTrue($loader->exists('@Contao/1.html.twig'));

        $loader->clear();

        $this->assertFalse($loader->exists('@Contao/1.html.twig'));
    }

    public function testPersistsAndRecallsPaths(): void
    {
        $path1 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/1');
        $path2 = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/2');

        $cacheAdapter = new ArrayAdapter();

        $loader1 = $this->getContaoFilesystemLoader($cacheAdapter);

        $loader1->addPath($path1);
        $loader1->addPath($path2, 'Contao_Foo');

        // Persist
        $this->assertEmpty(array_filter($cacheAdapter->getValues()));

        $loader1->persist();

        $this->assertNotEmpty(array_filter($cacheAdapter->getValues()));

        // Recall
        $loader2 = $this->getContaoFilesystemLoader($cacheAdapter);

        $this->assertSame([$path1], $loader2->getPaths());
        $this->assertSame([$path2], $loader2->getPaths('Contao_Foo'));
    }

    public function testGetCacheKey(): void
    {
        $loader = $this->getContaoFilesystemLoader(null, new TemplateLocator('/', [], []));

        $path = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/1');

        $loader->addPath($path, 'Contao', true);

        $this->assertSame(
            Path::join($path, '1.html.twig'),
            Path::normalize($loader->getCacheKey('@Contao/1.html.twig'))
        );
    }

    public function testGetCacheKeyDelegatesToThemeTemplate(): void
    {
        $basePath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $loader = $this->getContaoFilesystemLoader();

        $loader->addPath(Path::join($basePath, 'templates/'));
        $loader->addPath(Path::join($basePath, 'templates/my/theme'), 'Contao_Theme_my_theme');

        $this->assertSame(
            Path::join($basePath, 'templates/text.html.twig'),
            Path::normalize($loader->getCacheKey('@Contao/text.html.twig'))
        );

        // Reset and switch context
        $loader->reset();

        $page = new \stdClass();
        $page->templateGroup = 'templates/my/theme';

        $GLOBALS['objPage'] = $page;

        $this->assertSame(
            Path::join($basePath, 'templates/my/theme/text.html.twig'),
            Path::normalize($loader->getCacheKey('@Contao/text.html.twig'))
        );

        unset($GLOBALS['objPage']);
    }

    public function testGetSourceContext(): void
    {
        $loader = $this->getContaoFilesystemLoader();

        $path = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/paths/1');

        $loader->addPath($path);

        $source = $loader->getSourceContext('@Contao/1.html.twig');

        $this->assertSame('@Contao/1.html.twig', $source->getName());
        $this->assertSame(Path::join($path, '1.html.twig'), Path::normalize($source->getPath()));
    }

    public function testGetSourceContextDelegatesToThemeTemplate(): void
    {
        $basePath = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $loader = $this->getContaoFilesystemLoader();

        $loader->addPath(Path::join($basePath, 'templates/'));
        $loader->addPath(Path::join($basePath, 'templates/my/theme'), 'Contao_Theme_my_theme');

        $source = $loader->getSourceContext('@Contao/text.html.twig');

        $this->assertSame('@Contao/text.html.twig', $source->getName());
        $this->assertSame(Path::join($basePath, 'templates/text.html.twig'), Path::normalize($source->getPath()));

        // Reset and switch context
        $loader->reset();

        $page = new \stdClass();
        $page->templateGroup = 'templates/my/theme';

        $GLOBALS['objPage'] = $page;

        $source = $loader->getSourceContext('@Contao/text.html.twig');

        $this->assertSame('@Contao_Theme_my_theme/text.html.twig', $source->getName());
        $this->assertSame(Path::join($basePath, 'templates/my/theme/text.html.twig'), Path::normalize($source->getPath()));

        unset($GLOBALS['objPage']);
    }

    public function testGetDynamicParentAndHistory(): void
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures/Twig/inheritance');

        $bundles = [
            'CoreBundle' => 'class',
            'FooBundle' => ContaoModuleBundle::class,
            'BarBundle' => 'class',
            'App' => 'class',
        ];

        $bundlesMetadata = [
            'App' => ['path' => Path::join($projectDir, 'contao')],
            'FooBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/FooBundle')],
            'CoreBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/CoreBundle')],
            'BarBundle' => ['path' => Path::join($projectDir, 'vendor-bundles/BarBundle')],
        ];

        $locator = new TemplateLocator($projectDir, $bundles, $bundlesMetadata);

        $loader = $this->getContaoFilesystemLoader(null, $locator);

        $warmer = new ContaoFilesystemLoaderWarmer($loader, $locator, $projectDir, 'prod');
        $warmer->warmUp();

        $expectedChains = [
            'text' => [
                $globalPath = Path::join($projectDir, '/templates/text.html.twig') => '@Contao_Global/text.html.twig',
                $appPath = Path::join($projectDir, '/contao/templates/some/random/text.html.twig') => '@Contao_App/text.html.twig',
                $barPath = Path::join($projectDir, '/vendor-bundles/BarBundle/contao/templates/text.html.twig') => '@Contao_BarBundle/text.html.twig',
                $fooPath = Path::join($projectDir, '/vendor-bundles/FooBundle/templates/any/text.html.twig') => '@Contao_FooBundle/text.html.twig',
                $corePath = Path::join($projectDir, '/vendor-bundles/CoreBundle/Resources/contao/templates/text.html.twig') => '@Contao_CoreBundle/text.html.twig',
            ],
        ];

        // Full hierarchy
        $this->assertSame($expectedChains, $loader->getInheritanceChains());

        // Next element by path
        $this->assertSame(
            '@Contao_Global/text.html.twig',
            $loader->getDynamicParent('text.html.twig', 'other/template.html.twig'),
        );

        $this->assertSame(
            '@Contao_Global/text.html.twig',
            $loader->getDynamicParent('text', 'other/template.html.twig'),
        );

        $this->assertSame(
            '@Contao_App/text.html.twig',
            $loader->getDynamicParent('text.html.twig', $globalPath),
        );

        $this->assertSame(
            '@Contao_BarBundle/text.html.twig',
            $loader->getDynamicParent('text.html.twig', $appPath),
        );

        $this->assertSame(
            '@Contao_FooBundle/text.html.twig',
            $loader->getDynamicParent('text.html.twig', $barPath),
        );

        $this->assertSame(
            '@Contao_CoreBundle/text.html.twig',
            $loader->getDynamicParent('text.html.twig', $fooPath),
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("The template '$corePath' does not have a parent 'text' it can extend from.");

        $loader->getDynamicParent('text.html.twig', $corePath);
    }

    private function getContaoFilesystemLoader(AdapterInterface $cacheAdapter = null, TemplateLocator $templateLocator = null): ContaoFilesystemLoader
    {
        return new ContaoFilesystemLoader(
            $cacheAdapter ?? new NullAdapter(),
            $templateLocator ?? $this->createMock(TemplateLocator::class),
            '/',
        );
    }
}

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

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
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
        $loader->addPath($path1, 'Contao_Foo');

        $this->assertTrue($loader->exists('@Contao/1.html.twig'));
        $this->assertTrue($loader->exists('@Contao/2.html.twig'));
        $this->assertTrue($loader->exists('@Contao_Foo/1.html.twig'));
        $this->assertFalse($loader->exists('@Contao_Foo/2.html.twig'));
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

    // todo:
    //  - getCacheKey (with theme)
    //  - getSourceContext (with theme)
    //  - everything hierarchy related

    private function getContaoFilesystemLoader(AdapterInterface $cacheAdapter = null): ContaoFilesystemLoader
    {
        return new ContaoFilesystemLoader(
            $cacheAdapter ?? new NullAdapter(),
            $this->createMock(TemplateLocator::class)
        );
    }
}

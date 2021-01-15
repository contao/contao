<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\FailTolerantFilesystemLoader;
use Psr\Cache\CacheItemPoolInterface;
use Twig\Loader\FilesystemLoader;
use Webmozart\PathUtil\Path;

class FailTolerantFilesystemLoaderTest extends TestCase
{
    /**
     * @dataProvider providePaths
     */
    public function testAddPathDelegatesCallsIfCacheIsNotDirty(string $path): void
    {
        $inner = $this->createMock(FilesystemLoader::class);
        $inner
            ->expects($this->once())
            ->method('addPath')
            ->with($path, 'namespace')
        ;

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool
            ->method('hasItem')
            ->with(FailTolerantFilesystemLoader::CACHE_DIRTY_FLAG)
            ->willReturn(false)
        ;

        $filesystemLoader = new FailTolerantFilesystemLoader(
            $inner,
            $cacheItemPool,
            $this->getTestRoot()
        );

        $filesystemLoader->addPath($path, 'namespace');
    }

    /**
     * @dataProvider providePaths
     */
    public function testAddPathIgnoresMissingBundlePathsIfCacheIsDirty(string $path, bool $shouldDelegate): void
    {
        $inner = $this->createMock(FilesystemLoader::class);
        $inner
            ->expects($shouldDelegate ? $this->once() : $this->never())
            ->method('addPath')
            ->with($path, 'namespace')
        ;

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool
            ->method('hasItem')
            ->with(FailTolerantFilesystemLoader::CACHE_DIRTY_FLAG)
            ->willReturn(true)
        ;

        $filesystemLoader = new FailTolerantFilesystemLoader(
            $inner,
            $cacheItemPool,
            $this->getTestRoot()
        );

        $filesystemLoader->addPath($path, 'namespace');
    }

    public function providePaths(): \Generator
    {
        yield 'existing absolute path' => [
            Path::join($this->getTestRoot(), 'templates/other'),
            true,
        ];

        yield 'existing absolute bundle path' => [
            Path::join($this->getTestRoot(), 'templates/bundles/ContaoCoreBundle'),
            true,
        ];

        yield 'existing relative path' => [
            'templates/other',
            true,
        ];

        yield 'existing relative bundle path' => [
            'templates/bundles/ContaoCoreBundle',
            true,
        ];

        yield 'invalid absolute path' => [
            Path::join($this->getTestRoot(), 'templates/foo'),
            true,
        ];

        yield 'invalid absolute bundle path' => [
            Path::join($this->getTestRoot(), 'templates/bundles/Foo'),
            false,
        ];

        yield 'invalid relative path' => [
            'templates/foo',
            true,
        ];

        yield 'invalid relative bundle path' => [
            'templates/bundles/Foo',
            false,
        ];
    }

    private function getTestRoot(): string
    {
        return Path::canonicalize(__DIR__.'/../Fixtures/Twig');
    }
}

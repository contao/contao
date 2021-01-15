<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig;

use Psr\Cache\CacheItemPoolInterface;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Loader\SourceContextLoaderInterface;
use Twig\Source;
use Webmozart\PathUtil\Path;

/**
 * Twig template paths are registered at compile time but can be altered in the
 * Contao backend at runtime. This class acts as a proxy to the original
 * filesystem loader that ignores invalid bundle template paths if the
 * application cache is marked dirty.
 *
 * @internal
 */
class FailTolerantFilesystemLoader implements LoaderInterface, SourceContextLoaderInterface
{
    public const CACHE_DIRTY_FLAG = 'contao.template_path_cache_dirty';

    /**
     * @var FilesystemLoader
     */
    private $inner;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    private $bundleTemplatesDir;

    public function __construct(FilesystemLoader $inner, CacheItemPoolInterface $cache, string $projectDir)
    {
        $this->inner = $inner;
        $this->cache = $cache;
        $this->projectDir = $projectDir;
        $this->bundleTemplatesDir = Path::join($projectDir, 'templates/bundles');
    }

    public function addPath($path, $namespace = FilesystemLoader::MAIN_NAMESPACE): void
    {
        // Ignore missing bundle template paths if the application cache is dirty
        if ($this->cache->hasItem(self::CACHE_DIRTY_FLAG)) {
            $absolutePath = Path::makeAbsolute($path, $this->projectDir);

            if (Path::isBasePath($this->bundleTemplatesDir, $absolutePath) && !is_dir($absolutePath)) {
                return;
            }
        }

        $this->inner->addPath($path, $namespace);
    }

    public function getSourceContext($name): Source
    {
        return $this->inner->getSourceContext($name);
    }

    public function getCacheKey($name)
    {
        return $this->inner->getCacheKey($name);
    }

    public function isFresh($name, $time): bool
    {
        return $this->inner->isFresh($name, $time);
    }

    public function exists($name): bool
    {
        return $this->inner->exists($name);
    }
}

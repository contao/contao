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

use Contao\CoreBundle\Cache\ApplicationCacheState;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Loader\SourceContextLoaderInterface;
use Webmozart\PathUtil\Path;

/**
 * Twig template paths are registered at compile time but can be altered in the
 * Contao backend at runtime. This class therefore acts as a proxy to the
 * original filesystem loader that ignores invalid bundle template paths
 * if the application cache is marked dirty.
 *
 * @internal
 */
class FailTolerantFilesystemLoader implements LoaderInterface, SourceContextLoaderInterface
{
    /**
     * @var FilesystemLoader
     */
    private $inner;

    /**
     * @var ApplicationCacheState
     */
    private $cacheState;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var string
     */
    private $bundleTemplatesDir;

    public function __construct(FilesystemLoader $inner, ApplicationCacheState $cacheState, string $projectDir)
    {
        $this->inner = $inner;
        $this->cacheState = $cacheState;
        $this->projectDir = $projectDir;

        $this->bundleTemplatesDir = Path::join($projectDir, 'templates/bundles');
    }

    public function addPath($path, $namespace = FilesystemLoader::MAIN_NAMESPACE): void
    {
        // Allow missing bundle template paths if the application cache is dirty
        if ($this->cacheState->isDirty()) {
            $absolutePath = Path::makeAbsolute($path, $this->projectDir);

            if (Path::isBasePath($this->bundleTemplatesDir, $absolutePath) && !is_dir($absolutePath)) {
                return;
            }
        }

        $this->inner->addPath($path, $namespace);
    }

    public function getSourceContext($name)
    {
        return $this->inner->getSourceContext($name);
    }

    public function getCacheKey($name)
    {
        return $this->inner->getCacheKey($name);
    }

    public function isFresh($name, $time)
    {
        return $this->inner->isFresh($name, $time);
    }

    public function exists($name)
    {
        return $this->inner->exists($name);
    }
}

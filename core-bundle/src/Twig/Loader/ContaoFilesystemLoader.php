<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Loader;

use Contao\CoreBundle\Twig\Inheritance\HierarchyProvider;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Source;
use Webmozart\PathUtil\Path;

/**
 * The ContaoFilesystemLoader builds on top of Twig's FilesystemLoader but
 * introduces the following features/differences.
 *
 *  1) We persist paths in a cache pool and automatically load them at
 *     construct time. We also do not care about paths that do not exist at
 *     the point they are loaded/added/prepended.
 *
 *  2) The loader is sensitive to the page context and will delegate to other
 *     cache keys/source contexts if a matching variant exists in the current
 *     theme's namespace.
 *
 *  3) When adding paths, there is an option to 'track templates'. If enabled
 *     templates will be located and kept in a hierarchy. This allows us to
 *     support inheritance chains by dynamically rewriting 'extends'. Similar
 *     to the directory paths, the hierarchy is also cacheable and gets
 *     automatically restored at construct time.
 */
class ContaoFilesystemLoader extends FilesystemLoader implements HierarchyProvider, ResetInterface
{
    private const CACHE_KEY_PATHS = 'contao.twig.loader_paths';
    private const CACHE_KEY_HIERARCHY = 'contao.twig.template_hierarchy';

    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool;

    /**
     * @var TemplateLocator
     */
    private $templateLocator;

    /**
     * @var array<string,string>
     */
    private $trackedTemplatesPaths = [];

    /**
     * @var array<string,array<string,string>>|null
     */
    private $hierarchy;

    /**
     * @var string|false|null
     */
    private $currentThemeSlug;

    public function __construct(CacheItemPoolInterface $cachePool, TemplateLocator $templateLocator, string $rootPath = null)
    {
        parent::__construct([], $rootPath);

        $this->cachePool = $cachePool;
        $this->templateLocator = $templateLocator;

        // Restore paths from cache
        $pathsItem = $cachePool->getItem(self::CACHE_KEY_PATHS);

        if ($pathsItem->isHit()) {
            $this->paths = $pathsItem->get();
        }

        // Restore hierarchy from cache
        $hierarchyItem = $cachePool->getItem(self::CACHE_KEY_HIERARCHY);

        if ($hierarchyItem->isHit()) {
            $this->hierarchy = $hierarchyItem->get();
        }
    }

    /**
     * Adds a path where templates are stored (if it exists).
     *
     * If $trackTemplates is enabled, the path will be searched for templates
     * that should be available in the Contao template hierarchy. Choose
     * TRACKING_DEEP to include subdirectories and TRACKING_SHALLOW to only
     * search one level. This replaces the path used for tracking should there
     * have been any previously set for this namespace.
     *
     * @param string $path      A path where to look for templates
     * @param string $namespace A path namespace
     */
    public function addPath($path, $namespace = self::MAIN_NAMESPACE, bool $trackTemplates = false): void
    {
        try {
            parent::addPath($path, $namespace);
        } catch (LoaderError $error) {
            // Ignore

            return;
        }

        if ($trackTemplates) {
            // Use the real path that was added
            $path = $this->paths[$namespace][array_key_last($this->paths[$namespace])];

            $this->trackedTemplatesPaths[$path] = $namespace;
        }
    }

    /**
     * Prepends a path where templates are stored (if it exists).
     *
     * @param string $path      A path where to look for templates
     * @param string $namespace A path namespace
     */
    public function prependPath($path, $namespace = self::MAIN_NAMESPACE): void
    {
        try {
            parent::prependPath($path, $namespace);
        } catch (LoaderError $error) {
            // Ignore
        }
    }

    /**
     * Clears all registered template paths.
     */
    public function clear(): void
    {
        $this->paths = $this->trackedTemplatesPaths = $this->cache = $this->errorCache = [];

        $this->hierarchy = null;
    }

    /**
     * Writes the currently registered template paths and hierarchy to the
     * cache.
     */
    public function persist(): void
    {
        $pathsItem = $this->cachePool->getItem(self::CACHE_KEY_PATHS);
        $pathsItem->set($this->paths);
        $this->cachePool->save($pathsItem);

        $hierarchyItem = $this->cachePool->getItem(self::CACHE_KEY_HIERARCHY);
        $hierarchyItem->set($this->hierarchy);
        $this->cachePool->save($hierarchyItem);
    }

    /**
     * Gets the cache key to use for the environment's template cache for a
     * given template name.
     *
     * If we're currently in a theme context and a theme specific variant of
     * the template exists, it's cache key will be returned instead.
     *
     * @param string $name The name of the template to load
     *
     * @throws LoaderError When $name is not found
     *
     * @return string The cache key
     */
    public function getCacheKey($name): string
    {
        if (null !== ($themeTemplateName = $this->getThemeTemplateName($name))) {
            return parent::getCacheKey($themeTemplateName);
        }

        return parent::getCacheKey($name);
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * If we're currently in a theme context and a theme specific variant of
     * the template exists, it's source context will be returned instead.
     *
     * @param string $name The template logical name
     *
     * @throws LoaderError When $name is not found
     */
    public function getSourceContext($name): Source
    {
        if (null !== ($themeTemplateName = $this->getThemeTemplateName($name))) {
            return parent::getSourceContext($themeTemplateName);
        }

        return parent::getSourceContext($name);
    }

    /**
     * @internal
     *
     * Resets the cached theme context
     */
    public function reset(): void
    {
        $this->currentThemeSlug = null;
    }

    /**
     * Finds the next template in the hierarchy and returns the logical name.
     */
    public function getDynamicParent(string $shortNameOrIdentifier, string $sourcePath): string
    {
        if (null === $this->hierarchy) {
            $this->buildHierarchy();
        }

        $identifier = $this->getIdentifier($shortNameOrIdentifier);

        if (null === ($chain = $this->hierarchy[$identifier] ?? null)) {
            throw new \LogicException("The Contao extend target '$identifier' could not be found in the template hierarchy.");
        }

        // Find the next element in the hierarchy or use the first if it cannot be found
        $index = array_search($sourcePath, array_keys($chain), true);
        $next = array_values($chain)[false !== $index ? $index + 1 : 0] ?? null;

        if (null === $next) {
            throw new \LogicException("The template '$sourcePath' does not have a parent '$identifier' it can extend from.");
        }

        return $next;
    }

    /**
     * Refreshes the template hierarchy. Bear in mind that this will issue
     * filesystem operations for each of the tracked template paths.
     */
    public function buildHierarchy(): void
    {
        $templatesByNamespace = [];

        foreach ($this->trackedTemplatesPaths as $searchPath => $namespace) {
            $templates = $this->templateLocator->findTemplates($searchPath);

            foreach ($templates as $shortName => $templatePath) {
                $identifier = $this->getIdentifier($shortName);

                if (isset($templatesByNamespace[$namespace][$identifier])) {
                    throw new \OutOfBoundsException("There cannot be more than one '$identifier' template in '$searchPath'.");
                }

                $templatesByNamespace[$namespace][$identifier] = [$shortName, $templatePath];
            }
        }

        $hierarchy = [];

        foreach ($templatesByNamespace as $namespace => $templates) {
            foreach ($templates as $identifier => [$shortName, $path]) {
                if (!isset($hierarchy[$identifier])) {
                    $hierarchy[$identifier] = [];
                }

                $hierarchy[$identifier][$path] = "@$namespace/$shortName";
            }
        }

        $this->hierarchy = $hierarchy;
    }

    private function getIdentifier(string $shortName): string
    {
        // Strip .html5/.html.twig extension
        return preg_replace('/(.*)(\.html5|\.html.twig)/', '$1', $shortName);
    }

    /**
     * Returns the template name of a theme specific variant of the given name
     * or null if not applicable.
     */
    private function getThemeTemplateName(string $name): ?string
    {
        if (1 !== preg_match('%^@Contao/(.*)%', $name, $matches)) {
            return null;
        }

        if (false === ($themeSlug = $this->currentThemeSlug ?? $this->getThemeSlug())) {
            return null;
        }

        $template = "@Contao_Theme_$themeSlug/$matches[1]";

        return $this->exists($template) ? $template : null;
    }

    /**
     * Returns and stores the current theme slug or false if not applicable.
     *
     * @return string|false
     */
    private function getThemeSlug()
    {
        if (null === ($page = $GLOBALS['objPage'] ?? null) || null === ($path = $page->templateGroup)) {
            return $this->currentThemeSlug = false;
        }

        return $this->currentThemeSlug = TemplateLocator::createDirectorySlug(
            Path::makeRelative($path, 'templates')
        );
    }
}

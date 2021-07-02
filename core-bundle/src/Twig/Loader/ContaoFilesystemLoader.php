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

use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
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
class ContaoFilesystemLoader extends FilesystemLoader implements TemplateHierarchyInterface, ResetInterface
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
    private $inheritanceChains;

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

        if ($hierarchyItem->isHit() && null !== ($hierarchy = $hierarchyItem->get())) {
            $this->inheritanceChains = $hierarchy;
        }
    }

    /**
     * Adds a path where templates are stored (if it exists).
     *
     * If $trackTemplates is enabled, the path will be searched for templates
     * that should be available in the Contao template hierarchy.
     *
     * @param string $path      A path where to look for templates
     * @param string $namespace A "Contao" or "Contao_*" path namespace
     */
    public function addPath($path, $namespace = 'Contao', bool $trackTemplates = false): void
    {
        if (null === $this->parseContaoName("@$namespace")) {
            throw new LoaderError("Tried to register an invalid Contao namespace '$namespace'.");
        }

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
     * @param string $namespace A "Contao" or "Contao_*" path namespace
     */
    public function prependPath($path, $namespace = 'Contao'): void
    {
        if (null === $this->parseContaoName("@$namespace")) {
            throw new LoaderError("Tried to register an invalid Contao namespace '$namespace'.");
        }

        try {
            parent::prependPath($path, $namespace);
        } catch (LoaderError $error) {
            // Ignore
        }
    }

    public function getPaths($namespace = 'Contao'): array
    {
        return parent::getPaths($namespace);
    }

    /**
     * Clears all registered template paths.
     */
    public function clear(): void
    {
        $this->paths = $this->trackedTemplatesPaths = $this->cache = $this->errorCache = [];
        $this->inheritanceChains = null;
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
        $hierarchyItem->set($this->inheritanceChains);
        $this->cachePool->save($hierarchyItem);
    }

    /**
     * Gets the cache key to use for the environment's template cache for a
     * given template name.
     *
     * If we're currently in a theme context and a theme specific variant of
     * the template exists, its cache key will be returned instead.
     *
     * @param string $name The name of the template to load
     *
     * @throws LoaderError When $name is not found
     *
     * @return string The cache key
     */
    public function getCacheKey($name): string
    {
        $templateName = $this->getThemeTemplateName($name) ?? $name;

        return parent::getCacheKey($templateName);
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * If we're currently in a theme context and a theme specific variant of
     * the template exists, its source context will be returned instead.
     *
     * @param string $name The template logical name
     *
     * @throws LoaderError When $name is not found
     */
    public function getSourceContext($name): Source
    {
        $templateName = $this->getThemeTemplateName($name) ?? $name;

        $source = parent::getSourceContext($templateName);

        // The Contao PHP templates will still be rendered by the Contao
        // framework via a PhpTemplateProxyNode. We're removing the source to
        // not confuse Twig's lexer and parser and just keep the block names.
        // At some point we may transpile the source to valid Twig instead and
        // drop the proxy.
        if ('html5' !== Path::getExtension($source->getPath(), true)) {
            return $source;
        }

        preg_match_all(
            '/\$this\s*->\s*block\s*\(\s*[\'"]([a-z0-9_-]+)[\'"]\s*\)/i',
            file_get_contents($source->getPath()),
            $matches
        );

        return new Source(
            implode("\n", $matches[1] ?? []),
            $source->getName(),
            $source->getPath()
        );
    }

    /**
     * Check if we have the source code of a template, given its name.
     *
     * If we're currently in a theme context and a theme specific variant of
     * the template exists, its availability will be checked as well.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool If the template source code is handled by this loader or not
     */
    public function exists($name): bool
    {
        if (parent::exists($name)) {
            return true;
        }

        if (null !== ($themeTemplate = $this->getThemeTemplateName($name))) {
            return parent::exists($themeTemplate);
        }

        return false;
    }

    /**
     * Returns true if the template or any variant of it in the hierarchy is
     * still fresh.
     *
     * If we're currently in a theme context and a theme specific variant of
     * the template exists, its state will be checked as well.
     *
     * @param string $name The template name
     * @param int    $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @throws LoaderError When $name is not found
     *
     * @return bool true if the template is fresh, false otherwise
     */
    public function isFresh($name, $time): bool
    {
        if ((null !== ($themeTemplate = $this->getThemeTemplateName($name))) && !parent::isFresh($themeTemplate, $time)) {
            return false;
        }

        $chain = $this->getInheritanceChains()[$this->getIdentifier($name)] ?? [];

        foreach (array_keys($chain) as $path) {
            try {
                if (filemtime($path) > $time) {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
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

    public function getDynamicParent(string $shortNameOrIdentifier, string $sourcePath): string
    {
        $hierarchy = $this->getInheritanceChains();

        $identifier = $this->getIdentifier($shortNameOrIdentifier);

        if (null === ($chain = $hierarchy[$identifier] ?? null)) {
            throw new \LogicException("The template '$identifier' could not be found in the template hierarchy.");
        }

        // Find the next element in the hierarchy or use the first if it cannot be found
        $index = array_search(Path::canonicalize($sourcePath), array_keys($chain), true);
        $next = array_values($chain)[false !== $index ? $index + 1 : 0] ?? null;

        if (null === $next) {
            throw new \LogicException("The template '$sourcePath' does not have a parent '$identifier' it can extend from.");
        }

        return $next;
    }

    public function getFirst(string $shortNameOrIdentifier): string
    {
        $identifier = $this->getIdentifier($shortNameOrIdentifier);

        $hierarchy = $this->getInheritanceChains();

        if (null === ($chain = $hierarchy[$identifier] ?? null)) {
            throw new \LogicException("The template '$identifier' could not be found in the template hierarchy.");
        }

        return $chain[array_key_first($chain)];
    }

    public function getInheritanceChains(): array
    {
        if (null === $this->inheritanceChains) {
            $this->buildInheritanceChains();
        }

        return $this->inheritanceChains;
    }

    /**
     * Refreshes the template hierarchy. Bear in mind that this will induce
     * filesystem operations for each of the tracked template paths.
     */
    public function buildInheritanceChains(): void
    {
        $templatesByNamespace = [];

        foreach ($this->trackedTemplatesPaths as $searchPath => $namespace) {
            $templates = $this->templateLocator->findTemplates($searchPath);

            foreach ($templates as $shortName => $templatePath) {
                $identifier = $this->getIdentifier($shortName);

                if (isset($templatesByNamespace[$namespace][$identifier])) {
                    $basePath = Path::getLongestCommonBasePath($this->paths[$namespace]);

                    throw new \OutOfBoundsException("There cannot be more than one '$identifier' template in '$basePath'.");
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

        $this->inheritanceChains = $hierarchy;
    }

    /**
     * Split a Contao name into [namespace, short name]. The short name part
     * will be null if $name is only a namespace.
     *
     * If parsing fails - i.e. if the given name does not describe a "Contao"
     * or "Contao_*" namespace - null is returned instead.
     */
    private function parseContaoName(string $logicalNameOrNamespace): ?array
    {
        if (1 === preg_match('%^@(Contao(?:_[a-zA-Z0-9_-]+)?)(?:/(.*))?$%', $logicalNameOrNamespace, $matches)) {
            return [$matches[1], $matches[2] ?? null];
        }

        return null;
    }

    private function getIdentifier(string $name): string
    {
        return preg_replace('%(?:.*/)?(.*)(\.html5|\.html.twig)%', '$1', $name);
    }

    /**
     * Returns the template name of a theme specific variant of the given name
     * or null if not applicable.
     */
    private function getThemeTemplateName(string $name): ?string
    {
        if (null === ($parts = $this->parseContaoName($name)) || 'Contao' !== $parts[0]) {
            return null;
        }

        if (false === ($themeSlug = $this->currentThemeSlug ?? $this->getThemeSlug())) {
            return null;
        }

        $template = "@Contao_Theme_$themeSlug/$parts[1]";

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

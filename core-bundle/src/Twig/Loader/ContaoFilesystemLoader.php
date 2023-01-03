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

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Source;

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
 *  3) When adding paths, there is an option to "track templates". If enabled
 *     templates will be located and kept in a hierarchy. This allows us to
 *     support inheritance chains by dynamically rewriting "extends". Similar
 *     to the directory paths, the hierarchy is also cacheable and gets
 *     automatically restored at construct time.
 *
 * @experimental
 */
class ContaoFilesystemLoader extends FilesystemLoader implements TemplateHierarchyInterface, ResetInterface
{
    private const CACHE_KEY_PATHS = 'contao.twig.loader_paths';
    private const CACHE_KEY_HIERARCHY = 'contao.twig.template_hierarchy';

    private string|false|null $currentThemeSlug = null;

    /**
     * @var array<string,string>
     */
    private array $trackedTemplatesPaths = [];

    /**
     * @var array<string,array<string,string>>|null
     */
    private array|null $inheritanceChains = null;

    public function __construct(
        private CacheItemPoolInterface $cachePool,
        private TemplateLocator $templateLocator,
        private ThemeNamespace $themeNamespace,
        string $rootPath = null,
    ) {
        parent::__construct([], $rootPath);

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
    public function addPath(string $path, string $namespace = 'Contao', bool $trackTemplates = false): void
    {
        if (null === ContaoTwigUtil::parseContaoName("@$namespace")) {
            throw new LoaderError(sprintf('Tried to register an invalid Contao namespace "%s".', $namespace));
        }

        try {
            parent::addPath($path, $namespace);
        } catch (LoaderError) {
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
    public function prependPath(string $path, string $namespace = 'Contao'): void
    {
        if (null === ContaoTwigUtil::parseContaoName("@$namespace")) {
            throw new LoaderError(sprintf('Tried to register an invalid Contao namespace "%s".', $namespace));
        }

        try {
            parent::prependPath($path, $namespace);
        } catch (LoaderError) {
            // Ignore
        }
    }

    public function getPaths(string $namespace = 'Contao'): array
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
     * If we are currently in a theme context and a theme specific variant of
     * the template exists, its cache key will be returned instead.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key
     */
    public function getCacheKey(string $name): string
    {
        $templateName = $this->getThemeTemplateName($name) ?? $name;

        // We prefix the cache key to make sure templates from the default
        // Symfony loader won't be reused. Otherwise, we cannot reliably
        // differentiate when to apply our input encoding tolerant escaper
        // filters (see #4623).
        return 'c'.parent::getCacheKey($templateName);
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * If we're currently in a theme context and a theme specific variant of
     * the template exists, its source context will be returned instead.
     *
     * @param string $name The template logical name
     */
    public function getSourceContext(string $name): Source
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

        // Look up the blocks of the parent template if present
        if (
            1 === preg_match(
                '/\$this\s*->\s*extend\s*\(\s*[\'"]([a-z0-9_-]+)[\'"]\s*\)/i',
                (string) file_get_contents($source->getPath()),
                $match
            )
            && '@Contao/'.$match[1].'.html5' !== $name
        ) {
            return new Source($this->getSourceContext('@Contao/'.$match[1].'.html5')->getCode(), $source->getName(), $source->getPath());
        }

        preg_match_all(
            '/\$this\s*->\s*block\s*\(\s*[\'"]([a-z0-9_-]+)[\'"]\s*\)/i',
            (string) file_get_contents($source->getPath()),
            $matches
        );

        return new Source(implode("\n", $matches[1] ?? []), $source->getName(), $source->getPath());
    }

    /**
     * Check if we have the source code of a template, given its name.
     *
     * If we are currently in a theme context and a theme specific variant of
     * the template exists, its availability will be checked as well.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool If the template source code is handled by this loader or not
     */
    public function exists(string $name): bool
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
     * If we are currently in a theme context and a theme specific variant of
     * the template exists, its state will be checked as well.
     *
     * @param string $name The template name
     * @param int    $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template is fresh, false otherwise
     */
    public function isFresh(string $name, int $time): bool
    {
        if ((null !== ($themeTemplate = $this->getThemeTemplateName($name))) && !parent::isFresh($themeTemplate, $time)) {
            return false;
        }

        $chain = $this->getInheritanceChains()[ContaoTwigUtil::getIdentifier($name)] ?? [];

        foreach (array_keys($chain) as $path) {
            if (filemtime($path) > $time) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resets the cached theme context.
     *
     * @internal
     */
    public function reset(): void
    {
        $this->currentThemeSlug = null;
    }

    public function getDynamicParent(string $shortNameOrIdentifier, string $sourcePath, string $themeSlug = null): string
    {
        $hierarchy = $this->getInheritanceChains($themeSlug);
        $identifier = ContaoTwigUtil::getIdentifier($shortNameOrIdentifier);

        if (null === ($chain = $hierarchy[$identifier] ?? null)) {
            throw new \LogicException(sprintf('The template "%s" could not be found in the template hierarchy.', $identifier));
        }

        // Find the next element in the hierarchy or use the first if it cannot be found
        $index = array_search(Path::canonicalize($sourcePath), array_keys($chain), true);
        $next = array_values($chain)[false !== $index ? $index + 1 : 0] ?? null;

        if (null === $next) {
            throw new \LogicException(sprintf('The template "%s" does not have a parent "%s" it can extend from.', $sourcePath, $identifier));
        }

        return $next;
    }

    public function getFirst(string $shortNameOrIdentifier, string $themeSlug = null): string
    {
        $identifier = ContaoTwigUtil::getIdentifier($shortNameOrIdentifier);
        $hierarchy = $this->getInheritanceChains($themeSlug);

        if (null === ($chain = $hierarchy[$identifier] ?? null)) {
            throw new \LogicException(sprintf('The template "%s" could not be found in the template hierarchy.', $identifier));
        }

        return $chain[array_key_first($chain)];
    }

    public function getInheritanceChains(string $themeSlug = null): array
    {
        if (null === $this->inheritanceChains) {
            $this->buildInheritanceChains();
        }

        $chains = $this->inheritanceChains;

        foreach ($chains as $identifier => $chain) {
            foreach ($chain as $path => $name) {
                // Filter out theme paths that do not match the given slug.
                if (null !== ($namespace = $this->themeNamespace->match($name)) && $namespace !== $themeSlug) {
                    unset($chains[$identifier][$path]);
                }
            }

            if (empty($chains[$identifier])) {
                unset($chains[$identifier]);
            }
        }

        return $chains;
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
                if (isset($templatesByNamespace[$namespace][$shortName])) {
                    $basePath = Path::getLongestCommonBasePath(...$this->paths[$namespace]);

                    throw new \OutOfBoundsException(sprintf('There cannot be more than one "%s" template in "%s".', $shortName, $basePath));
                }

                $templatesByNamespace[$namespace][$shortName] = $templatePath;
            }
        }

        $typeByIdentifier = [];
        $hierarchy = [];

        foreach ($templatesByNamespace as $namespace => $templates) {
            foreach ($templates as $shortName => $path) {
                $identifier = ContaoTwigUtil::getIdentifier($shortName);

                $type = \in_array($extension = ContaoTwigUtil::getExtension($path), ['html.twig', 'html5'], true)
                    ? 'html.twig/html5'
                    : $extension;

                // Make sure all files grouped under a certain identifier share the same type
                if (null === ($existingType = $typeByIdentifier[$identifier] ?? null)) {
                    $typeByIdentifier[$identifier] = $type;
                } elseif ($type !== $existingType) {
                    throw new \OutOfBoundsException(sprintf('The "%s" template has incompatible types, got "%s" in "%s" and "%s" in "%s".', $identifier, $existingType, array_key_last($hierarchy[$identifier]), $type, $path));
                }

                $hierarchy[$identifier][$path] = "@$namespace/$shortName";
            }
        }

        $this->inheritanceChains = $hierarchy;
    }

    /**
     * Returns the template name of a theme specific variant of the given name
     * or null if not applicable.
     */
    private function getThemeTemplateName(string $name): string|null
    {
        $parts = ContaoTwigUtil::parseContaoName($name);

        if ('Contao' !== ($parts[0] ?? null)) {
            return null;
        }

        if (false === ($themeSlug = $this->currentThemeSlug ?? $this->getThemeSlug())) {
            return null;
        }

        $namespace = $this->themeNamespace->getFromSlug($themeSlug);
        $template = "$namespace/$parts[1]";

        return $this->exists($template) ? $template : null;
    }

    /**
     * Returns and stores the current theme slug or false if not applicable.
     */
    private function getThemeSlug(): string|false
    {
        if (null === ($page = $GLOBALS['objPage'] ?? null) || null === ($path = $page->templateGroup)) {
            return $this->currentThemeSlug = false;
        }

        $slug = $this->themeNamespace->generateSlug(Path::makeRelative($path, 'templates'));

        return $this->currentThemeSlug = $slug;
    }
}

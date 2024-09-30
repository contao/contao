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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\TemplateLoader;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * The ContaoFilesystemLoader loads templates from the Contao-specific
 * template directories inside of bundles (<bundle>/contao/templates), the
 * app's global template directory (<root>/templates) and registered theme
 * directories (<root>/templates/<theme>).
 *
 * Contrary to Twig's default loader, we keep track of template files instead of
 * directories. This allows us to group multiple representations of the same
 * template (identifier) from different namespaces in a single data structure: the
 * Contao template hierarchy.
 *
 * @experimental
 */
class ContaoFilesystemLoader implements LoaderInterface, ResetInterface
{
    private const CACHE_KEY_HIERARCHY = 'contao.twig.template_hierarchy';

    private string|false|null $currentThemeSlug = null;

    /**
     * @var array<string, array<string, string>>|null
     */
    private array|null $inheritanceChains = null;

    /**
     * @var array<string, string>
     */
    private array $lookupCache = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cachePool,
        private readonly TemplateLocator $templateLocator,
        private readonly ThemeNamespace $themeNamespace,
        private readonly ContaoFramework $framework,
        private readonly PageFinder $pageFinder,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Gets the cache key to use for the environment's template cache for a given
     * template name.
     *
     * If we are currently in a theme context and a theme specific variant of the
     * template exists, its cache key will be returned instead.
     *
     * @param string $name The name of the template to load
     *
     * @return string The cache key
     */
    public function getCacheKey(string $name): string
    {
        $templateName = $this->getThemeTemplateName($name) ?? $name;

        if (null === $path = $this->findTemplate($templateName)) {
            return '';
        }

        // We prefix the cache key to make sure templates from the default Symfony loader
        // won't be reused. Otherwise, we cannot reliably differentiate when to apply our
        // input encoding tolerant escaper filters (see #4623).
        return 'c:'.$path;
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * If we're currently in a theme context and a theme specific variant of the
     * template exists, its source context will be returned instead.
     *
     * @param string $name The template logical name
     */
    public function getSourceContext(string $name): Source
    {
        $templateName = $this->getThemeTemplateName($name) ?? $name;

        if (null === $path = $this->findTemplate($templateName)) {
            return new Source('', $templateName, '');
        }

        $path = Path::makeAbsolute($path, $this->projectDir);

        // The Contao PHP templates will still be rendered by the Contao framework via a
        // PhpTemplateProxyNode. We're removing the source to not confuse Twig's lexer
        // and parser and just keep the block names. At some point we may transpile the
        // source to valid Twig instead and drop the proxy.
        if ('html5' !== Path::getExtension($path, true)) {
            return new Source(file_get_contents($path), $templateName, $path);
        }

        $getExtendedTemplate = static function ($path): string|null {
            if (1 === preg_match('/\$this\s*->\s*extend\s*\(\s*[\'"]([a-z0-9_-]+)[\'"]\s*\)/i', (string) file_get_contents($path), $match)) {
                return $match[1];
            }

            return null;
        };

        // Use the default path of the template if it extends itself
        if (($extendedTemplate = $getExtendedTemplate($path)) && "@Contao/$extendedTemplate.html5" === $name) {
            $this->framework->initialize();
            $path = $this->framework->getAdapter(TemplateLoader::class)->getDefaultPath($extendedTemplate, 'html5');
        }

        // Look up the blocks of the parent template if present
        if (($extendedTemplate = $getExtendedTemplate($path)) && "@Contao/$extendedTemplate.html5" !== $name) {
            return new Source($this->getSourceContext("@Contao/$extendedTemplate.html5")->getCode(), $templateName, $path);
        }

        preg_match_all('/\$this\s*->\s*block\s*\(\s*[\'"]([a-z0-9_-]+)[\'"]\s*\)/i', (string) file_get_contents($path), $matches);

        return new Source(implode("\n", $matches[1]), $templateName, $path);
    }

    /**
     * Check if we have the source code of a template, given its name.
     *
     * If we are currently in a theme context and a theme specific variant of the
     * template exists, its availability will be checked as well.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool If the template source code is handled by this loader or not
     */
    public function exists(string $name): bool
    {
        if (null !== $this->findTemplate($name)) {
            return true;
        }

        if (null !== ($themeTemplate = $this->getThemeTemplateName($name))) {
            return null !== $this->findTemplate($themeTemplate);
        }

        return false;
    }

    /**
     * Returns true if the template or any variant of it in the hierarchy is still fresh.
     *
     * If we are currently in a theme context and a theme specific variant of the
     * template exists, its state will be checked as well.
     *
     * @param string $name The template name
     * @param int    $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @return bool true if the template is fresh, false otherwise
     */
    public function isFresh(string $name, int $time): bool
    {
        $isExpired = static function (string $path, int $time): bool {
            $mTime = @filemtime($path);

            // A cache record is considered expired if the actual file has a newer mtime or
            // reading the filemtime failed.
            return false === $mTime || $mTime > $time;
        };

        // Check theme template
        if ((null !== ($themeTemplate = $this->getThemeTemplateName($name))) && $isExpired($this->findTemplate($themeTemplate), $time)) {
            return false;
        }

        // Check hierarchy
        $chain = $this->getInheritanceChains()[ContaoTwigUtil::getIdentifier($name)] ?? [];

        foreach (array_keys($chain) as $path) {
            if ($isExpired($path, $time)) {
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
        $this->lookupCache = [];
    }

    /**
     * Finds the next template in the hierarchy and returns the logical name.
     */
    public function getDynamicParent(string $shortNameOrIdentifier, string $sourcePath, string|null $themeSlug = null): string
    {
        $hierarchy = $this->getInheritanceChains($themeSlug);
        $identifier = ContaoTwigUtil::getIdentifier($shortNameOrIdentifier);

        if (null === ($chain = $hierarchy[$identifier] ?? null)) {
            throw new \LogicException(\sprintf('The template "%s" could not be found in the template hierarchy.', $identifier));
        }

        // Find the next element in the hierarchy or use the first if it cannot be found
        $index = array_search(Path::canonicalize($sourcePath), array_keys($chain), true);
        $next = array_values($chain)[false !== $index ? $index + 1 : 0] ?? null;

        if (null === $next) {
            throw new \LogicException(\sprintf('The template "%s" does not have a parent "%s" it can extend from.', $sourcePath, $identifier));
        }

        return $next;
    }

    /**
     * Finds the first template in the hierarchy and returns the logical name.
     */
    public function getFirst(string $shortNameOrIdentifier, string|null $themeSlug = null): string
    {
        $identifier = ContaoTwigUtil::getIdentifier($shortNameOrIdentifier);
        $hierarchy = $this->getInheritanceChains($themeSlug);

        if (null === ($chain = $hierarchy[$identifier] ?? null)) {
            throw new \LogicException(\sprintf('The template "%s" could not be found in the template hierarchy.', $identifier));
        }

        return $chain[array_key_first($chain)];
    }

    /**
     * Returns an array [<template identifier> => <path mappings>] where path mappings
     * are arrays [<absolute path> => <template logical name>] in the order they
     * should appear in the inheritance chain for the respective template identifier.
     *
     * If a $themeSlug is given the result will additionally include templates of that
     * theme if there are any.
     *
     * For example:
     *   [
     *     'foo' => [
     *       '/path/to/foo.html.twig' => '@Some/foo.html.twig',
     *       '/other/path/to/foo.html5' => '@Other/foo.html5',
     *     ],
     *   ]
     *
     * @return array<string, array<string, string>>
     */
    public function getInheritanceChains(string|null $themeSlug = null): array
    {
        $this->ensureHierarchyIsBuilt();

        $chains = [];

        foreach ($this->inheritanceChains as $identifier => $chain) {
            foreach ($chain as $path => $name) {
                // Filter out theme paths that do not match the given slug.
                if (null !== ($namespace = $this->themeNamespace->match($name)) && $namespace !== $themeSlug) {
                    continue;
                }

                $chains[$identifier][Path::makeAbsolute($path, $this->projectDir)] = $name;
            }
        }

        return $chains;
    }

    /**
     * Warm up the template hierarchy cache.
     *
     * If $forceRefresh is enabled, any current state and cache state will get
     * rebuilt. This will always induce filesystem operations.
     */
    public function warmUp(bool $forceRefresh = false): void
    {
        if (!$forceRefresh) {
            $this->ensureHierarchyIsBuilt();

            return;
        }

        $this->inheritanceChains = null;
        $this->lookupCache = [];
        $this->ensureHierarchyIsBuilt(false);
    }

    private function ensureHierarchyIsBuilt(bool $useCacheForLookup = true): void
    {
        if (null !== $this->inheritanceChains) {
            return;
        }

        $hierarchyItem = $this->cachePool->getItem(self::CACHE_KEY_HIERARCHY);

        // Restore hierarchy from cache
        if ($useCacheForLookup && $hierarchyItem->isHit() && null !== ($hierarchy = $hierarchyItem->get())) {
            $this->inheritanceChains = $hierarchy;

            return;
        }

        // Find templates and build the hierarchy
        $this->inheritanceChains = $this->buildInheritanceChains();

        // Persist
        $hierarchyItem->set($this->inheritanceChains);
        $this->cachePool->save($hierarchyItem);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function buildInheritanceChains(): array
    {
        /** @var list<array{string, string}> $sources */
        $sources = [];

        foreach ($this->templateLocator->findThemeDirectories() as $slug => $path) {
            $sources[] = [$path, "Contao_Theme_$slug"];
        }

        $sources[] = [Path::join($this->projectDir, 'templates'), 'Contao_Global'];

        foreach ($this->templateLocator->findResourcesPaths() as $name => $resourcesPaths) {
            foreach ($resourcesPaths as $path) {
                $sources[] = [$path, "Contao_$name"];
            }
        }

        // Lookup templates and build hierarchy
        $templatesByNamespace = [];

        foreach ($sources as [$searchPath, $namespace]) {
            $templates = $this->templateLocator->findTemplates($searchPath);

            foreach ($templates as $shortName => $templatePath) {
                if (null !== ($existingPath = $templatesByNamespace[$namespace][$shortName] ?? null)) {
                    $basePath = Path::getLongestCommonBasePath($templatePath, $existingPath);

                    throw new \OutOfBoundsException(\sprintf('There cannot be more than one "%s" template in "%s".', $shortName, $basePath));
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
                    throw new \OutOfBoundsException(\sprintf('The "%s" template has incompatible types, got "%s" in "%s" and "%s" in "%s".', $identifier, $existingType, Path::makeAbsolute(array_key_last($hierarchy[$identifier]), $this->projectDir), $type, $path));
                }

                $hierarchy[$identifier][Path::makeRelative($path, $this->projectDir)] = "@$namespace/$shortName";
            }
        }

        return $hierarchy;
    }

    /**
     * Resolves the path of a given template name from the hierarchy or returns null
     * if no matching element was found.
     */
    private function findTemplate(string $name): string|null
    {
        $findTemplate = function (string $name): string|null {
            if (null === ($parsed = ContaoTwigUtil::parseContaoName($name))) {
                return null;
            }

            [$namespace, $shortname] = $parsed;
            $identifier = ContaoTwigUtil::getIdentifier($shortname);

            $this->ensureHierarchyIsBuilt();

            if (empty($candidates = $this->inheritanceChains[$identifier] ?? [])) {
                return null;
            }

            $extension = ContaoTwigUtil::getExtension($shortname);

            foreach ($candidates as $candidatePath => $candidateTemplateName) {
                // The extension needs to match.
                if (ContaoTwigUtil::getExtension($candidatePath) !== $extension) {
                    continue;
                }

                // Either the namespace must match, or - in case of the default namespace
                // ("@Contao") - the first non-theme element is used.
                if (('Contao' === $namespace && !$this->themeNamespace->match($candidateTemplateName)) || str_starts_with($candidateTemplateName, "@$namespace/")) {
                    return $candidatePath;
                }
            }

            return null;
        };

        // Cache the result in a lookup table
        return $this->lookupCache[$name] ??= $findTemplate($name);
    }

    /**
     * Returns the template name of a theme specific variant of the given name or null
     * if not applicable.
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
        if ((!$pageModel = $this->pageFinder->getCurrentPage()) || null === ($path = $pageModel->templateGroup)) {
            return $this->currentThemeSlug = false;
        }

        $slug = $this->themeNamespace->generateSlug(Path::makeRelative($path, 'templates'));

        return $this->currentThemeSlug = $slug;
    }
}

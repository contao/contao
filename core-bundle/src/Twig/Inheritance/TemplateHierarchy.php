<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inheritance;

/**
 * This class provides the inheritance hierarchy for all Contao templates.
 *
 * In Contao, templates can be inherited multiple times by bundles (in loading
 * order) and again by the application. The individual manifestation of each
 * template does only ever know the base template - supposedly living in our
 * special `Contao` namespace - that it may extend from. By default Twig does
 * *not* support multiple inheritance - only one template with the same name
 * is evaluated per namespace.
 *
 * So, in order to achieve an inheritance chain, we're registering the original
 * bundle templates under a distinct `Contao_<bundle>` namespace and dynamically
 * rewrite each 'extends' expression accordingly to form a chain.
 *
 * Examples:
 *
 * (1) extending `extra.html.twig` provided by `FooBundle` in a dynamic
 *     fashion (using the `Contao` namespace):
 *
 *     • text.html.twig extends Contao/text.html.twig (application)
 *
 *         ⟿
 *
 *     • text.html.twig extends Contao_FooBundle/text.html.twig
 *
 *
 * (2) `text.html.twig` provided by the core, extended multiple times:
 *
 *     • text.html.twig extends Contao/text.html.twig (application)
 *     • BarBundle/text.html.twig extends Contao/text.html.twig (loaded late)
 *     • FooBundle/text.html.twig extends Contao/text.html.twig (loaded early)
 *
 *         ⟿
 *
 *     • text.html.twig extends Contao_FooBundle/text.html.twig
 *     • FooBundle/text.html.twig extends Contao_BarBundle/text.html.twig
 *     • BarBundle/text.html.twig extends Contao_CoreBundle/text.html.twig
 *
 * Note: In each case the effective top-level template that a renderer should
 * receive will already be available under the `Contao` namespace by regular
 * ordering of namespace paths in the loader.
 */
class TemplateHierarchy
{
    /**
     * @var array<string, string>
     */
    private $appTemplates = [];

    /**
     * @var array<string,array<string,array<string>>>
     */
    private $templatesByTheme = [];

    /**
     * @var array<string,array<string,array<string>>>
     */
    private $templatesByBundle = [];

    /**
     * @var array<string>
     */
    private $bundlesOrder;

    /**
     * @var array<string,array<string,string>>|null
     */
    private $hierarchy;

    public function __construct(array $bundlesMetadata)
    {
        $this->bundlesOrder = array_keys($bundlesMetadata);
    }

    public function setAppTemplates(array $templates): void
    {
        $this->hierarchy = null;

        $this->appTemplates = $this->normalizeIdentifiers($templates);
    }

    public function setAppThemeTemplates(array $templates, string $themeSlug): void
    {
        $this->hierarchy = null;

        $this->templatesByTheme[$themeSlug] = $this->normalizeIdentifiers($templates);
    }

    public function setBundleTemplates(array $templates, string $bundle): void
    {
        $this->hierarchy = null;

        $this->templatesByBundle[$bundle] = $this->normalizeIdentifiers($templates);
    }

    /**
     * Finds the next template in the hierarchy and returns the fully qualified
     * name (namespace + shortname).
     *
     * @internal
     */
    public function getDynamicParent(string $shortNameOrIdentifier, string $sourcePath): string
    {
        if (null === $this->hierarchy) {
            $this->hierarchy = $this->buildHierarchy();
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

    public static function getAppNamespace(): string
    {
        return 'Contao_App';
    }

    public static function getAppThemeNamespace(string $themeSlug): string
    {
        return "Contao_App_$themeSlug";
    }

    public static function getBundleNamespace(string $bundle): string
    {
        return "Contao_$bundle";
    }

    private function normalizeIdentifiers(array $templates): array
    {
        $normalized = [];

        foreach ($templates as $shortName => $path) {
            $normalized[$this->getIdentifier($shortName)] = [$shortName, $path];
        }

        return $normalized;
    }

    private function getIdentifier(string $shortName): string
    {
        // Strip .html5/.html.twig extension
        return preg_replace('/(.*)(\.html5|\.html.twig)/', '$1', $shortName);
    }

    private function buildHierarchy(): array
    {
        $hierarchy = [];

        $append = static function (string $identifier, string $path, string $namespace, string $shortName) use (&$hierarchy): void {
            if (!isset($hierarchy[$identifier])) {
                $hierarchy[$identifier] = [];
            }

            $hierarchy[$identifier][$path] = "@$namespace/$shortName";
        };

        // (1) App theme templates
        foreach ($this->templatesByTheme as $theme => $templates) {
            $themeNamespace = self::getAppThemeNamespace($theme);

            foreach ($templates as $identifier => $template) {
                $append($identifier, $template[1], $themeNamespace, $template[0]);
            }
        }

        // (2) App templates
        $appNamespace = self::getAppNamespace();

        foreach ($this->appTemplates as $identifier => $template) {
            $append($identifier, $template[1], $appNamespace, $template[0]);
        }

        // (3) Bundle templates, sorted descending by bundle loading order
        $templatesByBundle = array_merge(
            array_intersect_key(array_reverse($this->bundlesOrder), $this->templatesByBundle),
            $this->templatesByBundle
        );

        foreach ($templatesByBundle as $bundle => $templates) {
            $bundleNamespace = self::getBundleNamespace($bundle);

            foreach ($templates as $identifier => $template) {
                $append($identifier, $template[1], $bundleNamespace, $template[0]);
            }
        }

        return $hierarchy;
    }
}

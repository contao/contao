<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Extension;

use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollectionInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Symfony\WebpackEncoreBundle\Asset\TagRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * This class provides twig functions to render theme assets in the page layout.
 *
 * Adapted from \Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension but uses a custom lookup collection.
 *
 * Per default, the Encore entrypoint of the currently active backend theme is used with a fallback to the Contao core
 * backend theme. Use $entrypointName = 'foobar' to explicitly load files from a backend theme with the "foobar" alias.
 *
 * To register a theme, use the `contao.backend_theme` servie tag.
 */
class EncoreEntryFilesTwigExtension extends AbstractExtension
{
    private EntrypointLookupCollectionInterface $entrypointCollection;
    private TagRenderer $tagRenderer;

    public function __construct(EntrypointLookupCollectionInterface $entrypointLookup, TagRenderer $tagRenderer)
    {
        $this->entrypointCollection = $entrypointLookup;
        $this->tagRenderer = $tagRenderer;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('contao_backend_js_files', [$this, 'getWebpackJsFiles']),
            new TwigFunction('contao_backend_css_files', [$this, 'getWebpackCssFiles']),
            new TwigFunction('contao_backend_script_tags', [$this, 'renderWebpackScriptTags'], ['is_safe' => ['html']]),
            new TwigFunction('contao_backend_link_tags', [$this, 'renderWebpackLinkTags'], ['is_safe' => ['html']]),
        ];
    }

    public function getWebpackJsFiles(string $entryName, string $entrypointName = null): array
    {
        return $this->getEntrypointLookup($entrypointName)
            ->getJavaScriptFiles($entryName)
        ;
    }

    public function getWebpackCssFiles(string $entryName, string $entrypointName = null): array
    {
        return $this->getEntrypointLookup($entrypointName)
            ->getCssFiles($entryName)
        ;
    }

    public function renderWebpackScriptTags(string $entryName, string $packageName = null, string $entrypointName = null, array $attributes = []): string
    {
        return $this->getTagRenderer()
            ->renderWebpackScriptTags($entryName, $packageName, $entrypointName, $attributes)
        ;
    }

    public function renderWebpackLinkTags(string $entryName, string $packageName = null, string $entrypointName = null, array $attributes = []): string
    {
        return $this->getTagRenderer()
            ->renderWebpackLinkTags($entryName, $packageName, $entrypointName, $attributes)
        ;
    }

    private function getEntrypointLookup(string $entrypointName): EntrypointLookupInterface
    {
        return $this->entrypointCollection
            ->getEntrypointLookup($entrypointName)
        ;
    }

    private function getTagRenderer(): TagRenderer
    {
        return $this->tagRenderer;
    }
}

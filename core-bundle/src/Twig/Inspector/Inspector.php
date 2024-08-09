<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inspector;

use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Psr\Cache\CacheItemPoolInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TemplateWrapper;

/**
 * @experimental
 */
class Inspector
{
    /**
     * @internal
     */
    public const CACHE_KEY = 'contao.twig.inspector';

    private readonly array $pathByTemplateName;

    public function __construct(
        private readonly Environment $twig,
        private readonly CacheItemPoolInterface $cachePool,
        private readonly ContaoFilesystemLoader $filesystemLoader,
    ) {
        $pathByTemplateName = [];

        foreach ($this->filesystemLoader->getInheritanceChains() as $chain) {
            foreach ($chain as $path => $name) {
                $pathByTemplateName[$name] = $path;
            }
        }

        $this->pathByTemplateName = $pathByTemplateName;
    }

    public function inspectTemplate(string $name): TemplateInformation
    {
        // Resolve the managed namespace to a specific one
        if (str_starts_with($name, '@Contao/')) {
            $name = $this->filesystemLoader->getFirst($name);
        }

        $blocks = $this->loadTemplate($name)->getBlockNames();
        $source = $this->twig->getLoader()->getSourceContext($name);
        $slots = [];

        // Accumulate data for the template as well as all statically set parents
        do {
            $data = $this->getData($name);
            $slots = array_unique([...$slots, ...$data['slots']]);
            $name = $data['parent'] ?? false;
        } while ($name);

        sort($blocks);
        sort($slots);

        return new TemplateInformation($source, $blocks, $slots);
    }

    private function loadTemplate(string $name): TemplateWrapper
    {
        try {
            return $this->twig->load($name);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            throw new InspectionException($name, $e);
        }
    }

    private function getData(string $templateName): array
    {
        // Make sure the template was compiled
        $this->twig->load($templateName);

        $cache = $this->cachePool->getItem(self::CACHE_KEY)->get();

        return $cache[$this->pathByTemplateName[$templateName] ?? null] ??
            throw new InspectionException($templateName, reason: 'No recorded information was found. Please clear the Twig template cache to make sure templates are recompiled.');
    }
}

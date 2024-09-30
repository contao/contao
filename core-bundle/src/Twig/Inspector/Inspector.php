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

        $blockNames = $this->loadTemplate($name)->getBlockNames();
        $source = $this->twig->getLoader()->getSourceContext($name);

        // Accumulate slots data for the template as well as all statically set parents
        $slots = [];

        do {
            $data = $this->getData($name);
            $slots = array_unique([...$slots, ...$data['slots']]);
            $name = $data['parent'] ?? false;
        } while ($name);

        sort($blockNames);
        sort($slots);

        return new TemplateInformation($source, $blockNames, $slots, $data['parent'], $data['uses']);
    }

    /**
     * @return list<BlockInformation>
     */
    public function getBlockHierarchy(string $baseTemplate, string $blockName): array
    {
        $data = $this->getData($baseTemplate);

        /** @var list<BlockInformation> $hierarchy */
        $hierarchy = [];

        $addBlock = static function (string $template, string $block, array $properties) use (&$hierarchy): void {
            $type = match ($properties[0] ?? null) {
                true => BlockType::enhance,
                false => BlockType::overwrite,
                default => BlockType::transparent,
            };

            $hierarchy[] = new BlockInformation($template, $block, $type, $properties[1] ?? false);
        };

        // Block in base template
        $addBlock($baseTemplate, $blockName, $data['blocks'][$blockName] ?? []);

        // Search used templates
        $blockImportedViaUse = false;
        $searchQueue = [...$data['uses']];

        while ([$currentTemplate, $currentOverwrites] = array_pop($searchQueue)) {
            $currentData = $this->getData($currentTemplate);

            foreach ($currentData['blocks'] as $name => $properties) {
                $importedName = $currentOverwrites[$name] ?? $name;

                if ($importedName === $blockName) {
                    $blockImportedViaUse = true;
                    $addBlock($currentTemplate, $name, $properties);
                }
            }

            $searchQueue = [...$searchQueue, ...$currentData['uses']];
        }

        // Walk up the inheritance tree
        if (!$blockImportedViaUse) {
            $currentData = $data;

            while ($parent = ($currentData['parent'] ?? false)) {
                $currentData = $this->getData($parent);
                $addBlock($parent, $blockName, $currentData['blocks'][$blockName] ?? []);
            }
        }

        // The last non-transparent template must be the origin. We fix the hierarchy by
        // adjusting the BlockType and removing everything that comes after.
        for ($i = \count($hierarchy) - 1; $i >= 0; --$i) {
            if (BlockType::transparent !== $hierarchy[$i]->getType()) {
                array_splice(
                    $hierarchy,
                    $i,
                    null,
                    [
                        new BlockInformation(
                            $hierarchy[$i]->getTemplateName(),
                            $hierarchy[$i]->getBlockName(),
                            BlockType::origin,
                            $hierarchy[$i]->isPrototype()),
                    ],
                );

                break;
            }
        }

        return $hierarchy;
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

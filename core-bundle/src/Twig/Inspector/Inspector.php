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

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @experimental
 */
class Inspector
{
    /**
     * @internal
     */
    private array $pathByTemplateName = [];

    public function __construct(
        private readonly Environment $twig,
        private readonly Storage $storage,
        private readonly ContaoFilesystemLoader $filesystemLoader,
    ) {
    }

    public function inspectTemplate(string $name): TemplateInformation
    {
        // Resolve the managed namespace to a specific one
        if (str_starts_with($name, '@Contao/')) {
            $name = $this->filesystemLoader->getFirst($name);
        }

        $loader = $this->twig->getLoader();

        try {
            $source = $loader->getSourceContext($name);
        } catch (LoaderError) {
            throw new InspectionException($name, reason: 'The template does not exist.');
        }

        $error = null;

        try {
            // Request blocks to trigger loading all parent templates
            $blockNames = $this->twig->load($name)->getBlockNames();
        } catch (LoaderError|SyntaxError $e) {
            // In case of a syntax or loader error we cannot inspect the template
            return new TemplateInformation($source, error: $e);
        } catch (RuntimeError $e) {
            $error = $e;
            $blockNames = [];
        }

        $data = $this->getData($name);

        $parent = $data['parent'];
        $uses = $data['uses'];
        $slots = [];

        // Accumulate slots data for the template as well as all statically set parents
        foreach ($this->getDataFromAll($data) as $parentData) {
            $slots = array_unique([...$slots, ...$parentData['slots']]);
        }

        sort($blockNames);
        sort($slots);

        return new TemplateInformation($source, $blockNames, $slots, $parent, $uses, $error, $data['deprecations']);
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
        $searchUsedTemplates = function (array $data) use ($blockName, $addBlock) {
            $searchQueue = [...$data['uses']];
            $blockImportedViaUse = false;

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

            return $blockImportedViaUse;
        };

        // Walk up the inheritance tree
        if (!$searchUsedTemplates($data)) {
            $currentData = $data;

            while ($parent = ($currentData['parent'] ?? false)) {
                $currentData = $this->getData($parent);

                if ($searchUsedTemplates($currentData)) {
                    break;
                }

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

    private function getDataFromAll(array $data): \Generator
    {
        yield $data;

        $parent = $data['parent'] ?? '';

        if (null !== ContaoTwigUtil::parseContaoName($parent)) {
            yield from $this->getDataFromAll($this->getData($parent));
        }
    }

    private function getData(string $templateName): array
    {
        // Make sure the template was compiled
        try {
            $this->twig->load($templateName);
        } catch (LoaderError|RuntimeError|SyntaxError) {
        }

        return $this->storage->get($this->getPathByTemplateName($templateName)) ??
            throw new InspectionException($templateName, reason: 'No recorded information was found. Please clear the Twig template cache to make sure templates are recompiled.');
    }

    private function getPathByTemplateName(string $templateName): string|null
    {
        if (null !== ($cachedPath = $this->pathByTemplateName[$templateName] ?? null)) {
            return $cachedPath;
        }

        // Rebuild cache if path was not found
        foreach ($this->filesystemLoader->getInheritanceChains(true) as $chain) {
            foreach ($chain as $path => $name) {
                $this->pathByTemplateName[$name] = $path;
            }
        }

        return $this->pathByTemplateName[$templateName] ?? null;
    }
}

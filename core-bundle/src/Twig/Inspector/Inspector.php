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
use Contao\CoreBundle\Twig\Defer\DeferTokenParser;
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
        if (!$this->filesystemLoader->exists($name)) {
            throw new InspectionException($name, reason: 'The template does not exist.');
        }

        // Resolve the managed namespace to a specific one
        if (str_starts_with($name, '@Contao/')) {
            $name = $this->filesystemLoader->getFirst($name);
        }

        $source = $this->twig->getLoader()->getSourceContext($name);

        $error = null;

        try {
            // Request blocks to trigger loading all parent templates
            $blockNames = array_filter(
                $this->twig->load($name)->getBlockNames(),
                static fn (string $name): bool => !str_starts_with($name, DeferTokenParser::PREFIX),
            );
        } catch (LoaderError|SyntaxError $e) {
            // In case of a syntax or loader error we cannot inspect the template
            return new TemplateInformation($source, error: $e);
        } catch (RuntimeError $e) {
            $error = $e;
            $blockNames = [];
        }

        sort($blockNames);

        $data = $this->getData($name);

        return new TemplateInformation(
            $source,
            $blockNames,
            $this->getSlots($data),
            $data['parent'],
            $data['uses'],
            $error,
        );
    }

    /**
     * @return list<BlockInformation>
     */
    public function getBlockHierarchy(string $baseTemplate, string $blockName): array
    {
        if (null === ContaoTwigUtil::parseContaoName($baseTemplate)) {
            return [];
        }

        // Resolve the managed namespace to a specific one
        if (str_starts_with($baseTemplate, '@Contao/')) {
            $baseTemplate = $this->filesystemLoader->getFirst($baseTemplate);
        }

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

        $baseData = [
            'name' => $templateName,
            'slots' => [],
            'blocks' => [],
            'nesting' => [],
            'calls' => [],
            'parent' => null,
            'uses' => [],
        ];

        if (null === ($path = $this->getPathByTemplateName($templateName))) {
            return $baseData;
        }

        $data = $this->storage->get($path) ??
            throw new InspectionException($templateName, reason: 'No recorded information was found. Please clear the Twig template cache to make sure templates are recompiled.');

        return [...$baseData, ...$data];
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

    /**
     * Accumulate slots from the template as well as all statically set parents but
     * ignore those living in overwritten blocks.
     *
     * @return list<string>
     */
    private function getSlots(array $data): array
    {
        /** @var list<string> $blocksToIgnore */
        $blocksToIgnore = [];

        /** @var array<string, list<string>> $blocksToKeep */
        $blocksToKeep = [];

        /** @var list<string> $slots */
        $slots = [];

        $isIgnoredBlock = static function (string $block, array $data) use (&$blocksToIgnore, &$blocksToKeep): bool {
            $iterateNesting = static function (callable $test) use ($block, $data) {
                $currentBlock = $block;

                do {
                    if ($test($currentBlock)) {
                        return true;
                    }
                } while ($currentBlock = $data['nesting'][$currentBlock] ?? false);

                return false;
            };

            // A block is not ignored if itself or any of its ancestors is explicitly marked
            // to be kept for the current template.
            if (
                null !== ($currentBlocksToKeep = ($blocksToKeep[$data['name']] ?? null))
                && $iterateNesting(static fn (string $currentBlock) => \in_array($currentBlock, $currentBlocksToKeep, true))
            ) {
                return false;
            }

            // A block is ignored if itself or any of its ancestors is marked to be ignored.
            return $iterateNesting(static fn (string $currentBlock) => \in_array($currentBlock, $blocksToIgnore, true));
        };

        // Accumulate slots data for the template as well as all statically set parents
        foreach ($this->getDataFromAll($data) as $currentData) {
            foreach ($currentData['slots'] as $slot => $block) {
                // Check if slot is in an ignored block
                if (null !== $block && $isIgnoredBlock($block, $currentData)) {
                    continue;
                }

                $slots[] = $slot;
            }

            foreach (array_keys($currentData['blocks']) as $block) {
                do {
                    if (BlockType::overwrite === $this->getBlockHierarchy($currentData['name'], $block)[0]->getType()) {
                        // This block overwrites the previous one
                        $blocksToIgnore[] = $block;
                        break;
                    }
                } while ($block = $currentData['nesting'][$block] ?? false);
            }

            foreach ($currentData['calls'] as $block) {
                foreach ($this->getBlockHierarchy($currentData['name'], $block) as $blockInformation) {
                    if (\in_array($blockInformation->getType(), [BlockType::origin, BlockType::overwrite], true)) {
                        $template = $blockInformation->getTemplateName();
                        $blocksToKeep[$template] = [...($blocksToKeep[$template] ?? []), $block];
                        break;
                    }
                }
            }
        }

        sort($slots);

        return $slots;
    }
}

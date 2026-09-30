<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\UserTemplate;

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Mcp\Exception\ToolCallException;

final class UserTemplateImpactAnalyzer
{
    public function __construct(
        private readonly ContaoFilesystemLoader $loader,
        private readonly Inspector $inspector,
    ) {
    }

    public function analyze(string $identifier, string|null $themeSlug, string|null $block = null): array
    {
        $chains = $this->loader->getInheritanceChains($themeSlug);
        $targetChain = $chains[$identifier] ?? null;

        if (!$targetChain) {
            throw new ToolCallException(\sprintf('The template "%s" does not exist in the selected theme context.', $identifier));
        }

        $targetNames = array_values($targetChain);
        $referenceNames = $this->getReferenceNames($identifier, $targetNames);
        $logicalNames = [];
        $reverseReferences = [];
        $unreadable = [];
        $dynamicReferenceCount = 0;

        foreach ($chains as $consumerIdentifier => $chain) {
            foreach (array_values($chain) as $position => $logicalName) {
                $logicalNames[$logicalName] = [$consumerIdentifier, $position];
            }
        }

        foreach ($logicalNames as $logicalName => [$consumerIdentifier, $position]) {
            try {
                $information = $this->inspector->inspectTemplate($logicalName);
            } catch (InspectionException) {
                $unreadable[] = $logicalName;

                continue;
            }

            foreach ($information->getReferences() as $reference) {
                if (null === $reference['name']) {
                    ++$dynamicReferenceCount;

                    continue;
                }

                $resolvedName = $this->resolveReference($reference['name'], $consumerIdentifier, $position, $chains, $logicalNames);

                if (null !== $resolvedName) {
                    $reverseReferences[$resolvedName][] = [
                        'template' => $logicalName,
                        'reference' => [
                            'type' => $reference['type'],
                            'name' => $reference['name'],
                            'line' => 0 < $reference['line'] ? $reference['line'] : null,
                        ],
                    ];
                }
            }
        }

        $targetName = $targetNames[0];
        $paths = [$targetName => []];
        $queue = [$targetName];

        while ($current = array_shift($queue)) {
            foreach ($reverseReferences[$current] ?? [] as $edge) {
                $consumerName = $edge['template'];

                if (isset($paths[$consumerName])) {
                    continue;
                }

                $paths[$consumerName] = [[...$edge, 'resolvesTo' => $current], ...$paths[$current]];
                $queue[] = $consumerName;
            }
        }

        $directConsumers = [];
        $transitiveConsumers = [];

        foreach ($chains as $consumerIdentifier => $chain) {
            $activeName = reset($chain);

            if (false === $activeName || $activeName === $targetName || !isset($paths[$activeName])) {
                continue;
            }

            $consumer = [
                'identifier' => $consumerIdentifier,
                'template' => $activeName,
                'depth' => \count($paths[$activeName]),
                'path' => $paths[$activeName],
            ];

            if (1 === $consumer['depth']) {
                $directConsumers[] = $consumer;
            } else {
                $transitiveConsumers[] = $consumer;
            }
        }

        $sortConsumers = static fn (array $a, array $b): int => $a['identifier'] <=> $b['identifier'];
        usort($directConsumers, $sortConsumers);
        usort($transitiveConsumers, $sortConsumers);

        $blockImpact = null;

        if (null !== $block && '' !== $block) {
            $blockImpact = [
                'name' => $block,
                'hierarchy' => array_map(
                    static fn ($information): array => [
                        'template' => $information->getTemplateName(),
                        'block' => $information->getBlockName(),
                        'type' => $information->getType()->value,
                        'prototype' => $information->isPrototype(),
                    ],
                    $this->inspector->getBlockHierarchy($targetName, $block),
                ),
            ];
        }

        return [
            'identifier' => $identifier,
            'theme' => $themeSlug,
            'inheritanceChain' => $targetNames,
            'referenceNames' => $referenceNames,
            'directConsumers' => $directConsumers,
            'transitiveConsumers' => $transitiveConsumers,
            'blockImpact' => $blockImpact,
            'summary' => [
                'directConsumerCount' => \count($directConsumers),
                'transitiveConsumerCount' => \count($transitiveConsumers),
                'affectedConsumerCount' => \count($directConsumers) + \count($transitiveConsumers),
                'unresolvedDynamicReferenceCount' => $dynamicReferenceCount,
            ],
            'limitations' => [
                'Only statically resolvable Twig references are matched to consumers.',
                'Dynamic template names and references created in PHP cannot be assigned to a target.',
            ],
            'unreadableTemplates' => array_values(array_unique($unreadable)),
        ];
    }

    private function resolveReference(string $referenceName, string $consumerIdentifier, int $position, array $chains, array $logicalNames): string|null
    {
        if (isset($logicalNames[$referenceName])) {
            return $referenceName;
        }

        if (!str_starts_with($referenceName, '@Contao/')) {
            return null;
        }

        $referencedIdentifier = ContaoTwigUtil::getIdentifier($referenceName);
        $referencedChain = array_values($chains[$referencedIdentifier] ?? []);

        if (!$referencedChain) {
            return null;
        }

        if ($referencedIdentifier === $consumerIdentifier) {
            return $referencedChain[$position + 1] ?? null;
        }

        return $referencedChain[0];
    }

    /**
     * @param list<string> $logicalNames
     *
     * @return list<string>
     */
    private function getReferenceNames(string $identifier, array $logicalNames): array
    {
        $referenceNames = $logicalNames;

        foreach ($logicalNames as $logicalName) {
            if (preg_match('~(?:^|/)'.preg_quote($identifier, '~').'(?<suffix>\..+)$~', $logicalName, $matches)) {
                $referenceNames[] = '@Contao/'.$identifier.$matches['suffix'];
            }
        }

        return array_values(array_unique($referenceNames));
    }
}

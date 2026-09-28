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

    public function analyze(string $identifier, string|null $themeSlug): array
    {
        $chains = $this->loader->getInheritanceChains($themeSlug);
        $targetChain = $chains[$identifier] ?? null;

        if (!$targetChain) {
            throw new ToolCallException(\sprintf('The template "%s" does not exist in the selected theme context.', $identifier));
        }

        $targetNames = array_values($targetChain);
        $referenceNames = $this->getReferenceNames($identifier, $targetNames);
        $consumers = [];
        $unreadable = [];

        foreach ($chains as $consumerIdentifier => $chain) {
            $logicalName = reset($chain);

            if (false === $logicalName || \in_array($logicalName, $targetNames, true)) {
                continue;
            }

            try {
                $information = $this->inspector->inspectTemplate($logicalName);
            } catch (InspectionException) {
                $unreadable[] = $logicalName;

                continue;
            }

            $relations = [];

            foreach ($information->getReferences() as $reference) {
                if (null !== $reference['name'] && \in_array($reference['name'], $referenceNames, true)) {
                    $relations[] = [
                        'type' => $reference['type'],
                        'line' => $reference['line'],
                    ];
                }
            }

            if (!$relations) {
                continue;
            }

            $consumers[$consumerIdentifier] = [
                'identifier' => $consumerIdentifier,
                'template' => $logicalName,
                'references' => $relations,
            ];
        }

        ksort($consumers);

        return [
            'identifier' => $identifier,
            'theme' => $themeSlug,
            'inheritanceChain' => $targetNames,
            'referenceNames' => $referenceNames,
            'directConsumers' => array_values($consumers),
            'summary' => [
                'directConsumerCount' => \count($consumers),
            ],
            'limitations' => [
                'Only statically resolvable Twig references are matched to consumers.',
                'Dynamic template names and references created in PHP cannot be assigned to a target.',
                'The result lists direct consumers and does not calculate transitive impact.',
            ],
            'unreadableTemplates' => array_values(array_unique($unreadable)),
        ];
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

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
class SearchIndexerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    private const DELEGATING_SERVICE_ID = 'contao.search.indexer.delegating';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(self::DELEGATING_SERVICE_ID)) {
            return;
        }

        $definition = $container->findDefinition(self::DELEGATING_SERVICE_ID);
        $references = $this->findAndSortTaggedServices('contao.search_indexer', $container);

        // Make sure we do not add the delegating indexer to itself to prevent endless redirects
        $references = array_filter(
            $references,
            static function (Reference $reference): bool {
                return self::DELEGATING_SERVICE_ID !== (string) $reference;
            }
        );

        // Remove the service and the search index listener if there are no indexers
        if (0 === \count($references)) {
            $container->removeDefinition(self::DELEGATING_SERVICE_ID);
            $container->removeDefinition('contao.listener.search_index');

            return;
        }

        foreach ($references as $reference) {
            $definition->addMethodCall('addIndexer', [$reference]);
        }
    }
}

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

    private const DELEGATING_SERVICE_ID = 'contao.search.delegating_indexer';

    public function process(ContainerBuilder $container): void
    {
        $indexers = $this->findAndSortTaggedServices('contao.search_indexer', $container);

        // Make sure we do not add the delegating indexer to itself to prevent endless redirects
        $indexers = array_filter(
            $indexers,
            static fn (Reference $reference): bool => self::DELEGATING_SERVICE_ID !== (string) $reference
        );

        if (!$container->hasDefinition(self::DELEGATING_SERVICE_ID) || 0 === \count($indexers)) {
            // Remove delegating indexer
            $container->removeDefinition(self::DELEGATING_SERVICE_ID);

            // Remove search index listener
            $container->removeDefinition('contao.listener.search_index');

            // Remove search index message handler
            $container->removeDefinition('contao.messenger.message_handler.search_index');

            // Remove search index crawl subscriber
            $container->removeDefinition('contao.crawl.escargot.search_index_subscriber');

            return;
        }

        $definition = $container->findDefinition(self::DELEGATING_SERVICE_ID);

        foreach ($indexers as $reference) {
            $definition->addMethodCall('addIndexer', [$reference]);
        }

        // Add an alias for the delegating service
        $container->setAlias('contao.search.indexer', self::DELEGATING_SERVICE_ID)->setPublic(true);
    }
}

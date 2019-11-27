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

class SearchIndexerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    private const DELEGATING_SERVICE_ID = 'contao.search.indexer.delegating';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(self::DELEGATING_SERVICE_ID)) {
            return;
        }

        $definition = $container->findDefinition(self::DELEGATING_SERVICE_ID);
        $references = $this->findAndSortTaggedServices('contao.search_indexer', $container);

        // Make sure we don't add the delegating indexer to itself to prevent endless redirects
        $references = array_filter($references, static function (Reference $reference) {
            return self::DELEGATING_SERVICE_ID !== (string) $reference;
        });

        if (0 === \count($references)) {
            $container->removeDefinition(self::DELEGATING_SERVICE_ID);

            // Also remove the search index listener
            $container->removeDefinition('contao.listener.search_index');

            return;
        }

        foreach ($references as $reference) {
            $definition->addMethodCall('addIndexer', [$reference]);
        }
    }
}

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

use Contao\CoreBundle\Search\Indexer\DelegatingIndexer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SearchIndexerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(DelegatingIndexer::class)) {
            return;
        }

        $definition = $container->findDefinition(DelegatingIndexer::class);
        $references = $this->findAndSortTaggedServices('contao.search_indexer', $container);

        foreach ($references as $reference) {
            $definition->addMethodCall('addIndexer', [$reference]);
        }
    }
}

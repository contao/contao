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

        foreach ($references as $reference) {
            // Make sure we don't add ourselves to prevent endless redirects
            if (self::DELEGATING_SERVICE_ID === (string) $reference) {
                continue;
            }

            $definition->addMethodCall('addIndexer', [$reference]);
        }
    }
}

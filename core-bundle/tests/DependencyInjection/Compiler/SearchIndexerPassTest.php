<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\SearchIndexerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SearchIndexerPassTest extends TestCase
{
    public function testAddsTheIndexersIfThereIsADelegatingIndexer(): void
    {
        $container = new ContainerBuilder();

        $delegatingDefinition = new Definition();
        $delegatingDefinition->addTag('contao.search_indexer');
        $container->setDefinition('contao.search.indexer.delegating', $delegatingDefinition);

        $definition = new Definition();
        $definition->addTag('contao.search_indexer');
        $container->setDefinition('contao.search.super-indexer', $definition);

        $pass = new SearchIndexerPass();
        $pass->process($container);

        $methodCalls = $container->findDefinition('contao.search.indexer.delegating')->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addIndexer', $methodCalls[0][0]);
        $this->assertInstanceOf(Reference::class, $methodCalls[0][1][0]);
    }
}

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
        $container->setDefinition('contao.search.delegating_indexer', $delegatingDefinition);

        $definition = new Definition();
        $definition->addTag('contao.search_indexer');
        $container->setDefinition('contao.search.super-indexer', $definition);

        $container->setDefinition('contao.listener.search_index', new Definition());
        $container->setDefinition('contao.crawl.escargot.search_index_subscriber', new Definition());

        $pass = new SearchIndexerPass();
        $pass->process($container);

        $methodCalls = $container->findDefinition('contao.search.delegating_indexer')->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addIndexer', $methodCalls[0][0]);
        $this->assertInstanceOf(Reference::class, $methodCalls[0][1][0]);

        $this->assertTrue($container->hasDefinition('contao.listener.search_index'));
        $this->assertTrue($container->hasDefinition('contao.crawl.escargot.search_index_subscriber'));
        $this->assertTrue($container->hasAlias('contao.search.indexer'));
        $this->assertTrue($container->getAlias('contao.search.indexer')->isPublic());
    }

    public function testRemovesTheDelegatingIndexerAndDisablesTheListenerAndCrawlSubscriberIfNoIndexersWereGiven(): void
    {
        $container = new ContainerBuilder();

        $delegatingDefinition = new Definition();
        $delegatingDefinition->addTag('contao.search_indexer');
        $container->setDefinition('contao.search.delegating_indexer', $delegatingDefinition);

        $container->setDefinition('contao.listener.search_index', new Definition());
        $container->setDefinition('contao.crawl.escargot.search_index_subscriber', new Definition());

        $pass = new SearchIndexerPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('contao.search.delegating_indexer'));
        $this->assertFalse($container->hasDefinition('contao.listener.search_index'));
        $this->assertFalse($container->hasDefinition('contao.crawl.escargot.search_index_subscriber'));
        $this->assertFalse($container->hasAlias('contao.search.indexer'));
    }
}

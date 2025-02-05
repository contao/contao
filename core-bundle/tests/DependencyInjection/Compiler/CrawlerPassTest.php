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

use Contao\CoreBundle\DependencyInjection\Compiler\CrawlerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CrawlerPassTest extends TestCase
{
    public function testAddsTheSubscribersIfTheFactoryExistsAndSubscribersAreRegistered(): void
    {
        $definition = new Definition();
        $definition->addTag('contao.escargot_subscriber');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.search.subscriber.super-subscriber', $definition);
        $container->setDefinition('contao.crawl.escargot.factory', new Definition());
        $container->setDefinition('contao.command.crawl', new Definition());

        $pass = new CrawlerPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('contao.crawl.escargot.factory'));
        $this->assertTrue($container->hasDefinition('contao.command.crawl'));

        $methodCalls = $container->findDefinition('contao.crawl.escargot.factory')->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addSubscriber', $methodCalls[0][0]);
        $this->assertInstanceOf(Reference::class, $methodCalls[0][1][0]);
    }

    public function testRemovesTheFactoryAndTheCrawlCommandIfThereAreNoSubscribers(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.crawl.escargot.factory', new Definition());
        $container->setDefinition('contao.command.crawl', new Definition());

        $pass = new CrawlerPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('contao.crawl.escargot.factory'));
        $this->assertFalse($container->hasDefinition('contao.command.crawl'));
    }
}

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

use Contao\CoreBundle\DependencyInjection\Compiler\EscargotSubscriberPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EscargotSubscriberPassTest extends TestCase
{
    public function testAddsTheSubscribers(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.crawl.escargot_factory', new Definition());

        $definition = new Definition();
        $definition->addTag('contao.escargot_subscriber');
        $container->setDefinition('contao.search.subscriber.super-subscriber', $definition);

        $pass = new EscargotSubscriberPass();
        $pass->process($container);

        $methodCalls = $container->findDefinition('contao.crawl.escargot_factory')->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addSubscriber', $methodCalls[0][0]);
        $this->assertInstanceOf(Reference::class, $methodCalls[0][1][0]);
    }
}

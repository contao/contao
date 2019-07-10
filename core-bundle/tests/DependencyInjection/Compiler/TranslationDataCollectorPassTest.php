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

use Contao\CoreBundle\DependencyInjection\Compiler\TranslationDataCollectorPass;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TranslationDataCollectorPassTest extends TestCase
{
    public function testReturnsIfTheDataCollectorServiceDoesNotExist(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('translator.data_collector')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('getDefinition')
        ;

        $pass = new TranslationDataCollectorPass();
        $pass->process($container);
    }

    public function testSetsTheDecoratedService(): void
    {
        $definition = new Definition();
        $definition->setArgument(0, new Reference('translator.data_collector'));

        $container = new ContainerBuilder();
        $container->setDefinition('translator.data_collector', new Definition());
        $container->setDefinition('contao.translation.translator.data_collector', new Definition());
        $container->setDefinition('data_collector.translation', $definition);

        $pass = new TranslationDataCollectorPass();
        $pass->process($container);

        $dataCollector = $container->getDefinition('data_collector.translation');

        $this->assertSame('contao.translation.translator.data_collector', (string) $dataCollector->getArgument(0));

        $contaoDataCollector = $container->getDefinition('contao.translation.translator.data_collector');

        $this->assertSame(['translator', null, 0], $contaoDataCollector->getDecoratedService());
    }
}

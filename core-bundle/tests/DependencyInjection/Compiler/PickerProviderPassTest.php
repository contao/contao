<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\PickerProviderPass;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PickerProviderPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new PickerProviderPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\PickerProviderPass', $pass);
    }

    public function testAddsTheProvidersToThePickerBuilder(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.picker.builder', new Definition());

        $definition = new Definition();
        $definition->addTag('contao.picker_provider');

        $container->setDefinition('contao.picker.page_provider', $definition);

        $pass = new PickerProviderPass();
        $pass->process($container);

        $methodCalls = $container->findDefinition('contao.picker.builder')->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('addProvider', $methodCalls[0][0]);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $methodCalls[0][1][0]);
        $this->assertSame('contao.picker.page_provider', (string) $methodCalls[0][1][0]);
    }

    public function testDoesNotLookForDefinitionsIfThereIsNoPickerBuilder(): void
    {
        $definition = new Definition();
        $definition->addTag('contao.picker_provider');

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['findDefinition'])
            ->getMock()
        ;

        $container->setDefinition('contao.picker.page_provider', $definition);

        $pass = new PickerProviderPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('contao.picker_provider'));
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class FragmentRegistryPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new FragmentRegistryPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\FragmentRegistryPass', $pass);
    }

    public function testAddsTheMethodCallsToTheDefinition(): void
    {
        $container = new ContainerBuilder();

        $loader = new YamlFileLoader(
            $container,
            new FileLocator([
                __DIR__.'/../../../src/Resources/config',
                __DIR__.'/../../Fixtures/FragmentRegistry',
            ])
        );

        $loader->load('services.yml'); // real configuration
        $loader->load('example.yml'); // fixture data

        $pass = new FragmentRegistryPass();
        $pass->process($container);

        $methodCalls = $container->getDefinition('contao.fragment.registry')->getMethodCalls();

        $this->assertSame('addFragment', $methodCalls[0][0]);
        $this->assertSame('contao.fragment.content_element.other', $methodCalls[0][1][0]);

        $this->assertSame(
            [
                'type' => 'other',
                'category' => 'text',
                'renderStrategy' => 'esi',
                'tag' => 'contao.fragment.content_element',
                'controller' => 'other_controller:foobarAction', // validates method option
            ],
            $methodCalls[0][1][2]
        );

        $this->assertSame('addFragment', $methodCalls[1][0]);
        $this->assertSame('contao.fragment.content_element.other', $methodCalls[1][1][0]);

        $this->assertSame(
            [
                'type' => 'other',
                'category' => 'maintenance',
                'tag' => 'contao.fragment.content_element',
                'controller' => 'other_controller:secondAction', // validates method option
            ],
            $methodCalls[1][1][2]
        );

        $this->assertSame('addFragment', $methodCalls[2][0]);
        $this->assertSame('contao.fragment.frontend_module.navigation_trivial', $methodCalls[2][1][0]);

        $this->assertSame(
            [
                'type' => 'navigation_trivial',
                'category' => 'navigationMenu',
                'tag' => 'contao.fragment.frontend_module',
                'controller' => 'AppBundle\TestTrivialModule',
            ],
            $methodCalls[2][1][2]
        );

        $this->assertSame('addFragment', $methodCalls[3][0]);
        $this->assertSame('contao.fragment.frontend_module.navigation_esi', $methodCalls[3][1][0]);

        $this->assertSame(
            [
                'type' => 'navigation_esi',
                'category' => 'navigationMenu',
                'renderStrategy' => 'esi',
                'tag' => 'contao.fragment.frontend_module',
                'controller' => 'AppBundle\TestEsiModule',
            ],
            $methodCalls[3][1][2]
        );

        $this->assertSame('addFragment', $methodCalls[4][0]);
        $this->assertSame('contao.fragment.page_type.super_page', $methodCalls[4][1][0]);

        $this->assertSame(
            [
                'type' => 'super_page',
                'tag' => 'contao.fragment.page_type',
                'controller' => 'AppBundle\SuperPageType',
            ],
            $methodCalls[4][1][2]
        );
    }

    public function testFailsIfATaggedServiceHasNoTypeAttribute(): void
    {
        $container = new ContainerBuilder();

        $loader = new YamlFileLoader(
            $container,
            new FileLocator([
                __DIR__.'/../../../src/Resources/config',
                __DIR__.'/../../Fixtures/FragmentRegistry',
            ])
        );

        $loader->load('services.yml');
        $loader->load('invalid.yml');

        $pass = new FragmentRegistryPass();

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('must have a "type" attribute');

        $pass->process($container);
    }

    public function testDoesNotLookForDefinitionsIfThereIsNoFragmentRegistry(): void
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['findDefinition'])
            ->getMock()
        ;

        $container
            ->expects($this->never())
            ->method('findDefinition')
        ;

        $loader = new YamlFileLoader(
            $container,
            new FileLocator([__DIR__.'/../../Fixtures/FragmentRegistry'])
        );

        $loader->load('example.yml');

        $pass = new FragmentRegistryPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('contao.fragment.registry'));
    }

    public function testDoesNotLookForDefinitionsIfThereAreNoFragmentRenderers(): void
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['findDefinition'])
            ->getMock()
        ;

        $container
            ->expects($this->never())
            ->method('findDefinition')
        ;

        $reflection = new \ReflectionClass(FragmentRegistryPass::class);
        $method = $reflection->getMethod('registerFragmentRenderer');
        $method->setAccessible(true);
        $registry = $reflection->newInstanceWithoutConstructor();

        $method->invokeArgs($registry, [$container, 'foo', 'bar']);
    }
}

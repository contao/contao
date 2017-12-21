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

use Contao\CoreBundle\DependencyInjection\Compiler\RegisterHookListenersPass;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterHookListenersPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new RegisterHookListenersPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\RegisterHookListenersPass', $pass);
    }

    public function testRegistersTheHookListeners(): void
    {
        $attributes = [
            'hook' => 'initializeSystem',
            'method' => 'onInitializeSystem',
            'priority' => 10,
        ];

        $definition = new Definition('Test\HookListener');
        $definition->addTag('contao.hook', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertSame(
            [
                'initializeSystem' => [
                    10 => [
                        ['test.hook_listener', 'onInitializeSystem'],
                    ],
                ],
            ],
            $this->getHookListenersFromDefinition($container)[0]
        );
    }

    public function testGeneratesMethodNameIfNoneGiven(): void
    {
        $attributes = [
            'hook' => 'generatePage',
        ];

        $definition = new Definition('Test\HookListener');
        $definition->addTag('contao.hook', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertSame(
            [
                'generatePage' => [
                    0 => [
                        ['test.hook_listener', 'onGeneratePage'],
                    ],
                ],
            ],
            $this->getHookListenersFromDefinition($container)[0]
        );
    }

    public function testSetsTheDefaultPriorityIfNoPriorityGiven(): void
    {
        $attributes = [
            'hook' => 'initializeSystem',
            'method' => 'onInitializeSystem',
        ];

        $definition = new Definition('Test\HookListener');
        $definition->addTag('contao.hook', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertSame(
            [
                'initializeSystem' => [
                    0 => [
                        ['test.hook_listener', 'onInitializeSystem'],
                    ],
                ],
            ],
            $this->getHookListenersFromDefinition($container)[0]
        );
    }

    public function testHandlesMultipleTags(): void
    {
        $definition = new Definition('Test\HookListener');

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystemFirst',
            ]
        );

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'generatePage',
                'method' => 'onGeneratePage',
            ]
        );

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystemSecond',
            ]
        );

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'parseTemplate',
                'method' => 'onParseTemplate',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertSame(
            [
                'initializeSystem' => [
                    0 => [
                        ['test.hook_listener', 'onInitializeSystemFirst'],
                        ['test.hook_listener', 'onInitializeSystemSecond'],
                    ],
                ],
                'generatePage' => [
                    0 => [
                        ['test.hook_listener', 'onGeneratePage'],
                    ],
                ],
                'parseTemplate' => [
                    0 => [
                        ['test.hook_listener', 'onParseTemplate'],
                    ],
                ],
            ],
            $this->getHookListenersFromDefinition($container)[0]
        );
    }

    public function testSortsTheHooksByPriority(): void
    {
        $definitionA = new Definition('Test\HookListenerA');

        $definitionA->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystem',
                'priority' => 10,
            ]
        );

        $definitionB = new Definition('Test\HookListenerB');

        $definitionB->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystemLow',
                'priority' => 10,
            ]
        );

        $definitionB->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystemHigh',
                'priority' => 100,
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener.a', $definitionA);
        $container->setDefinition('test.hook_listener.b', $definitionB);

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertSame(
            [
                'initializeSystem' => [
                    100 => [
                        ['test.hook_listener.b', 'onInitializeSystemHigh'],
                    ],
                    10 => [
                        ['test.hook_listener.a', 'onInitializeSystem'],
                        ['test.hook_listener.b', 'onInitializeSystemLow'],
                    ],
                ],
            ],
            $this->getHookListenersFromDefinition($container)[0]
        );
    }

    public function testDoesNothingIfThereIsNoFramework(): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        $container
            ->method('hasDefinition')
            ->with('contao.framework')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('findTaggedServiceIds')
        ;

        $pass = new RegisterHookListenersPass();
        $pass->process($container);
    }

    public function testDoesNothingIfThereAreNoHooks(): void
    {
        $container = $this->getContainerBuilder();

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $definition = $container->getDefinition('contao.framework');

        $this->assertEmpty($definition->getMethodCalls());
    }

    public function testFailsIfTheHookAttributeIsMissing(): void
    {
        $definition = new Definition('Test\HookListener');
        $definition->addTag('contao.hook', ['method' => 'onInitializeSystemAfter']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();

        $this->expectException(InvalidConfigurationException::class);

        $pass->process($container);
    }

    /**
     * Returns the hook listeners from the container definition.
     *
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getHookListenersFromDefinition(ContainerBuilder $container): array
    {
        $this->assertTrue($container->hasDefinition('contao.framework'));

        $definition = $container->getDefinition('contao.framework');
        $methodCalls = $definition->getMethodCalls();

        $this->assertInternalType('array', $methodCalls);
        $this->assertSame('setHookListeners', $methodCalls[0][0]);
        $this->assertInternalType('array', $methodCalls[0][1]);

        return $methodCalls[0][1];
    }

    /**
     * Returns the container builder with a dummy contao.framework definition.
     *
     * @return ContainerBuilder
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.framework', new Definition(ContaoFramework::class, []));

        return $container;
    }
}

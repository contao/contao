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

use Contao\CoreBundle\DependencyInjection\Compiler\RegisterHookListenersPass;
use Contao\CoreBundle\Fixtures\EventListener\InvokableListener;
use Contao\CoreBundle\Fixtures\EventListener\TestListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterHookListenersPassTest extends TestCase
{
    public function testRegistersTheHookListeners(): void
    {
        $attributes = [
            'hook' => 'initializeSystem',
            'method' => 'onInitializeSystem',
            'priority' => 10,
            'private' => false,
        ];

        $definition = new Definition(TestListener::class);
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

    public function testMakesHookListenersPublic(): void
    {
        $attributes = [
            'hook' => 'initializeSystem',
            'method' => 'onInitializeSystem',
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.hook', $attributes);
        $definition->setPublic(false);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $this->assertFalse($container->findDefinition('test.hook_listener')->isPublic());

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertTrue($container->findDefinition('test.hook_listener')->isPublic());
    }

    public function testGeneratesMethodNameIfNoneGiven(): void
    {
        $attributes = [
            'hook' => 'generatePage',
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.hook', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertSame(
            [
                'generatePage' => [[
                    ['test.hook_listener', 'onGeneratePage'],
                ]],
            ],
            $this->getHookListenersFromDefinition($container)[0]
        );
    }

    public function testUsesInvokeMethodIfNoneGiven(): void
    {
        $attributes = [
            'hook' => 'generatePage',
        ];

        $definition = new Definition(InvokableListener::class);
        $definition->addTag('contao.hook', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertSame(
            [
                'generatePage' => [[
                    ['test.hook_listener', '__invoke'],
                ]],
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

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.hook', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();
        $pass->process($container);

        $this->assertSame(
            [
                'initializeSystem' => [[
                    ['test.hook_listener', 'onInitializeSystem'],
                ]],
            ],
            $this->getHookListenersFromDefinition($container)[0]
        );
    }

    public function testHandlesMultipleTags(): void
    {
        $definition = new Definition(TestListener::class);

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
                'initializeSystem' => [[
                    ['test.hook_listener', 'onInitializeSystemFirst'],
                    ['test.hook_listener', 'onInitializeSystemSecond'],
                ]],
                'generatePage' => [[
                    ['test.hook_listener', 'onGeneratePage'],
                ]],
                'parseTemplate' => [[
                    ['test.hook_listener', 'onParseTemplate'],
                ]],
            ],
            $this->getHookListenersFromDefinition($container)[0]
        );
    }

    public function testSortsTheHooksByPriority(): void
    {
        $definitionA = new Definition(TestListener::class);

        $definitionA->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystem',
                'priority' => 10,
            ]
        );

        $definitionB = new Definition(TestListener::class);

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
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.hook', ['method' => 'onInitializeSystemAfter']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();

        $this->expectException(InvalidDefinitionException::class);

        $pass->process($container);
    }

    public function testThrowsExceptionIfConfiguredMethodDoesNotExist(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.hook', ['hook' => 'onInitializeSystem', 'method' => 'onFoo']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();

        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('The class "Contao\CoreBundle\Fixtures\EventListener\TestListener" does not have a method "onFoo".');

        $pass->process($container);
    }

    public function testThrowsExceptionIfConfiguredMethodIsPrivate(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.hook', ['hook' => 'onInitializeSystem', 'method' => 'onPrivateCallback']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();

        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('The "Contao\CoreBundle\Fixtures\EventListener\TestListener::onPrivateCallback" method exists but is not public.');

        $pass->process($container);
    }

    public function testThrowsExceptionIfGeneratedMethodIsPrivate(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.hook', ['hook' => 'privateCallback']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();

        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('The "Contao\CoreBundle\Fixtures\EventListener\TestListener::onPrivateCallback" method exists but is not public.');

        $pass->process($container);
    }

    public function testThrowsExceptionIfNoValidMethodExists(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.hook', ['hook' => 'fooBar']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHookListenersPass();

        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('Either specify a method name or implement the "onFooBar" or __invoke method.');

        $pass->process($container);
    }

    /**
     * @return array<int, array<string, array<int, array<int, array<string>>>>>
     */
    private function getHookListenersFromDefinition(ContainerBuilder $container): array
    {
        $this->assertTrue($container->hasDefinition('contao.framework'));

        $definition = $container->getDefinition('contao.framework');
        $methodCalls = $definition->getMethodCalls();

        $this->assertIsArray($methodCalls);
        $this->assertSame('setHookListeners', $methodCalls[0][0]);
        $this->assertIsArray($methodCalls[0][1]);

        return $methodCalls[0][1];
    }

    /**
     * Returns the container builder with a dummy "contao.framework" definition.
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.framework', new Definition(ContaoFramework::class, []));

        return $container;
    }
}

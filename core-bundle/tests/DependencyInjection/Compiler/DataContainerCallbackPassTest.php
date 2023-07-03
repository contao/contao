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

use Contao\CoreBundle\DependencyInjection\Compiler\DataContainerCallbackPass;
use Contao\CoreBundle\EventListener\DataContainerCallbackListener;
use Contao\CoreBundle\Fixtures\EventListener\InvokableListener;
use Contao\CoreBundle\Fixtures\EventListener\TestListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DataContainerCallbackPassTest extends TestCase
{
    public function testRegistersTheHookListeners(): void
    {
        $attributes = [
            'table' => 'tl_page',
            'target' => 'config.onload_callback',
            'method' => 'onLoadPage',
            'priority' => 10,
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        10 => [
                            ['test.callback_listener', 'onLoadPage'],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testMakesHookListenersPublic(): void
    {
        $attributes = [
            'table' => 'tl_page',
            'target' => 'config.onload_callback',
            'method' => 'onLoadPage',
            'priority' => 10,
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);
        $definition->setPublic(false);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $this->assertFalse($container->findDefinition('test.callback_listener')->isPublic());

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertTrue($container->findDefinition('test.callback_listener')->isPublic());
    }

    public function testGeneratesMethodNameIfNoneGiven(): void
    {
        $attributes = [
            'table' => 'tl_page',
            'target' => 'config.onload_callback',
            'priority' => 10,
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        10 => [
                            ['test.callback_listener', 'onLoadCallback'],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testUsesInvokeMethodIfNoneGiven(): void
    {
        $attributes = [
            'table' => 'tl_page',
            'target' => 'config.onload_callback',
            'priority' => 10,
        ];

        $definition = new Definition(InvokableListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        10 => [
                            ['test.callback_listener', '__invoke'],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testSetsTheDefaultPriorityIfNoPriorityGiven(): void
    {
        $attributes = [
            'table' => 'tl_page',
            'target' => 'config.onload_callback',
            'method' => 'onLoadPage',
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_page' => [
                    'config.onload_callback' => [[
                        ['test.callback_listener', 'onLoadPage'],
                    ]],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testAppendsCallbackSuffixIfNotGiven(): void
    {
        $attributes = [
            'table' => 'tl_page',
            'target' => 'config.onload',
            'priority' => 10,
            'method' => 'onLoadPage',
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        10 => [
                            ['test.callback_listener', 'onLoadPage'],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testDoesNotAppendCallbackSuffixForWizard(): void
    {
        $attributes = [
            'table' => 'tl_content',
            'target' => 'fields.article.wizard',
            'priority' => 10,
            'method' => 'onArticleWizard',
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_content' => [
                    'fields.article.wizard' => [
                        10 => [
                            ['test.callback_listener', 'onArticleWizard'],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testDoesNotAppendCallbackSuffixForXlabel(): void
    {
        $attributes = [
            'table' => 'tl_content',
            'target' => 'fields.listitems.xlabel',
            'priority' => 10,
            'method' => 'onListitemsXlabel',
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_content' => [
                    'fields.listitems.xlabel' => [
                        10 => [
                            ['test.callback_listener', 'onListitemsXlabel'],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testDoesNotAppendCallbackSuffixForPanelCallback(): void
    {
        $attributes = [
            'table' => 'tl_content',
            'target' => 'list.sorting.panel_callback.foobar',
            'method' => 'onFoobarCallback',
        ];

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_content' => [
                    'list.sorting.panel_callback.foobar' => [[
                        ['test.callback_listener', 'onFoobarCallback'],
                    ]],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testHandlesMultipleCallbacks(): void
    {
        $definition = new Definition(TestListener::class);

        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_page',
                'target' => 'config.onload',
                'method' => 'loadFirst',
            ]
        );

        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_page',
                'target' => 'config.onload',
                'method' => 'loadSecond',
            ]
        );

        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_article',
                'target' => 'fields.title.load',
            ]
        );

        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_article',
                'target' => 'fields.title.save',
            ]
        );

        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_content',
                'target' => 'list.sorting.child_record_callback',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_page' => [
                    'config.onload_callback' => [[
                        ['test.callback_listener', 'loadFirst'],
                        ['test.callback_listener', 'loadSecond'],
                    ]],
                ],
                'tl_article' => [
                    'fields.title.load_callback' => [[
                        ['test.callback_listener', 'onLoadCallback'],
                    ]],
                    'fields.title.save_callback' => [[
                        ['test.callback_listener', 'onSaveCallback'],
                    ]],
                ],
                'tl_content' => [
                    'list.sorting.child_record_callback' => [[
                        ['test.callback_listener', 'onChildRecordCallback'],
                    ]],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testAddsTheCallbacksByPriority(): void
    {
        $definitionA = new Definition(TestListener::class);

        $definitionA->addTag(
            'contao.callback',
            [
                'table' => 'tl_page',
                'target' => 'config.onload',
                'priority' => 10,
            ]
        );

        $definitionB = new Definition(TestListener::class);

        $definitionB->addTag(
            'contao.callback',
            [
                'table' => 'tl_page',
                'target' => 'config.onload',
                'method' => 'onLoadFirst',
                'priority' => 10,
            ]
        );

        $definitionB->addTag(
            'contao.callback',
            [
                'table' => 'tl_page',
                'target' => 'config.onload',
                'method' => 'onLoadSecond',
                'priority' => 100,
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener.a', $definitionA);
        $container->setDefinition('test.callback_listener.b', $definitionB);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        10 => [
                            ['test.callback_listener.a', 'onLoadCallback'],
                            ['test.callback_listener.b', 'onLoadFirst'],
                        ],
                        100 => [
                            ['test.callback_listener.b', 'onLoadSecond'],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0]
        );
    }

    public function testDoesNothingIfThereIsNoListener(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('hasDefinition')
            ->with('contao.listener.data_container_callback')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('findTaggedServiceIds')
        ;

        $pass = new DataContainerCallbackPass();
        $pass->process($container);
    }

    public function testDoesNothingIfThereAreNoHooks(): void
    {
        $container = $this->getContainerBuilder();

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $definition = $container->getDefinition('contao.listener.data_container_callback');

        $this->assertEmpty($definition->getMethodCalls());
    }

    public function testFailsIfTheTableAttributeIsMissing(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', ['target' => 'config.onload']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();

        $this->expectException(InvalidDefinitionException::class);

        $pass->process($container);
    }

    public function testFailsIfTheTargetAttributeIsMissing(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', ['table' => 'tl_page']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();

        $this->expectException(InvalidDefinitionException::class);

        $pass->process($container);
    }

    public function testThrowsExceptionIfConfiguredMethodDoesNotExist(): void
    {
        $definition = new Definition(TestListener::class);

        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_page',
                'target' => 'tl_page.config.foo',
                'method' => 'onFooCallback',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();

        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('The class "Contao\CoreBundle\Fixtures\EventListener\TestListener" does not have a method "onFooCallback".');

        $pass->process($container);
    }

    public function testThrowsExceptionIfConfiguredMethodIsPrivate(): void
    {
        $definition = new Definition(TestListener::class);

        $definition->addTag(
            'contao.callback',
            [
                'table' => 'tl_page',
                'target' => 'tl_page.config.foo',
                'method' => 'onPrivateCallback',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();

        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('The "Contao\CoreBundle\Fixtures\EventListener\TestListener::onPrivateCallback" method exists but is not public.');

        $pass->process($container);
    }

    public function testThrowsExceptionIfGeneratedMethodIsPrivate(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', ['table' => 'tl_page', 'target' => 'tl_page.config.private']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();

        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('The "Contao\CoreBundle\Fixtures\EventListener\TestListener::onPrivateCallback" method exists but is not public.');

        $pass->process($container);
    }

    public function testThrowsExceptionIfNoValidMethodExists(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', ['table' => 'tl_page', 'target' => 'tl_page.config.foo']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();

        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('Either specify a method name or implement the "onFooCallback" or __invoke method.');

        $pass->process($container);
    }

    /**
     * @return array<int, array<string, array<string, array<int, array<int, array<string>>>>>>
     */
    private function getCallbacksFromDefinition(ContainerBuilder $container): array
    {
        $this->assertTrue($container->hasDefinition('contao.listener.data_container_callback'));

        $definition = $container->getDefinition('contao.listener.data_container_callback');
        $methodCalls = $definition->getMethodCalls();

        $this->assertIsArray($methodCalls);
        $this->assertSame('setCallbacks', $methodCalls[0][0]);
        $this->assertIsArray($methodCalls[0][1]);

        return $methodCalls[0][1];
    }

    /**
     * Returns the container builder with a dummy contao.framework definition.
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setDefinition(
            'contao.listener.data_container_callback',
            new Definition(DataContainerCallbackListener::class, [])
        );

        return $container;
    }
}

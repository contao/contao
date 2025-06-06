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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DataContainerCallbackPassTest extends TestCase
{
    public function testRegistersTheCallbackListeners(): void
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
                            [
                                'service' => 'test.callback_listener',
                                'method' => 'onLoadPage',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
        );
    }

    public function testResolvesChildDefinitions(): void
    {
        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', [
            'table' => 'tl_module',
            'target' => 'fields.imageSize.options',
            'method' => 'onOptions',
        ]);

        $childDefinition = new ChildDefinition('test.parent.listener');
        $childDefinition->addTag('contao.callback', [
            'table' => 'tl_module',
            'target' => 'fields.otherField.options',
            'method' => 'onOptions',
        ]);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.parent.listener', $definition);
        $container->setDefinition('test.child.listener', $childDefinition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_module' => [
                    'fields.imageSize.options_callback' => [
                        [
                            [
                                'service' => 'test.parent.listener',
                                'method' => 'onOptions',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                    'fields.otherField.options_callback' => [
                        [
                            [
                                'service' => 'test.child.listener',
                                'method' => 'onOptions',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
        );

        $this->assertTrue($container->findDefinition('test.parent.listener')->isPublic());
        $this->assertTrue($container->findDefinition('test.child.listener')->isPublic());
    }

    public function testMakesCallbackListenersPublic(): void
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
                            [
                                'service' => 'test.callback_listener',
                                'method' => 'onLoadCallback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
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
                            [
                                'service' => 'test.callback_listener',
                                'method' => '__invoke',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
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
                        [
                            'service' => 'test.callback_listener',
                            'method' => 'onLoadPage',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
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
                            [
                                'service' => 'test.callback_listener',
                                'method' => 'onLoadPage',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
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
                            [
                                'service' => 'test.callback_listener',
                                'method' => 'onArticleWizard',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
        );
    }

    /**
     * @dataProvider noSuffixProvider
     */
    public function testDoesNotAppendCallbackSuffix(array $attributes, array $expected): void
    {
        $attributes['table'] = 'tl_content';

        $definition = new Definition(TestListener::class);
        $definition->addTag('contao.callback', $attributes);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_content' => $expected,
            ],
            $this->getCallbacksFromDefinition($container)[0],
        );
    }

    public static function noSuffixProvider(): iterable
    {
        yield 'xlabel callback' => [
            [
                'target' => 'fields.listitems.xlabel',
                'priority' => 10,
                'method' => 'onListitemsXlabel',
            ],
            [
                'fields.listitems.xlabel' => [
                    10 => [[
                        'service' => 'test.callback_listener',
                        'method' => 'onListitemsXlabel',
                        'closure' => null,
                        'singleton' => null,
                    ]],
                ],
            ],
        ];

        yield 'panel callback' => [
            [
                'target' => 'list.sorting.panel_callback.foobar',
                'method' => 'onFoobarCallback',
            ],
            [
                'list.sorting.panel_callback.foobar' => [[
                    [
                        'service' => 'test.callback_listener',
                        'method' => 'onFoobarCallback',
                        'closure' => null,
                        'singleton' => null,
                    ],
                ]],
            ],
        ];

        yield 'default callback' => [
            [
                'target' => 'fields.article.default',
                'priority' => 1,
                'method' => 'onFoobarCallback',
            ],
            [
                'fields.article.default' => [
                    1 => [[
                        'service' => 'test.callback_listener',
                        'method' => 'onFoobarCallback',
                        'closure' => null,
                        'singleton' => null,
                    ]],
                ],
            ],
        ];

        yield 'exact attribute is true' => [
            [
                'target' => 'fields.foo.barCallback',
                'priority' => 1,
                'method' => 'onFoobarCallback',
                'exact' => true,
            ],
            [
                'fields.foo.barCallback' => [
                    1 => [[
                        'service' => 'test.callback_listener',
                        'method' => 'onFoobarCallback',
                        'closure' => null,
                        'singleton' => null,
                    ]],
                ],
            ],
        ];
    }

    public function testHandlesMultipleCallbacks(): void
    {
        $definition = new Definition(TestListener::class);

        $definition->addTag('contao.callback', [
            'table' => 'tl_page',
            'target' => 'config.onload',
            'method' => 'loadFirst',
        ]);

        $definition->addTag('contao.callback', [
            'table' => 'tl_page',
            'target' => 'config.onload',
            'method' => 'loadSecond',
        ]);

        $definition->addTag('contao.callback', [
            'table' => 'tl_article',
            'target' => 'fields.title.load',
        ]);

        $definition->addTag('contao.callback', [
            'table' => 'tl_article',
            'target' => 'fields.title.save',
        ]);

        $definition->addTag('contao.callback', [
            'table' => 'tl_content',
            'target' => 'list.sorting.child_record_callback',
        ]);

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.callback_listener', $definition);

        $pass = new DataContainerCallbackPass();
        $pass->process($container);

        $this->assertSame(
            [
                'tl_page' => [
                    'config.onload_callback' => [[
                        [
                            'service' => 'test.callback_listener',
                            'method' => 'loadFirst',
                            'closure' => null,
                            'singleton' => null,
                        ],
                        [
                            'service' => 'test.callback_listener',
                            'method' => 'loadSecond',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
                'tl_article' => [
                    'fields.title.load_callback' => [[
                        [
                            'service' => 'test.callback_listener',
                            'method' => 'onLoadCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                    'fields.title.save_callback' => [[
                        [
                            'service' => 'test.callback_listener',
                            'method' => 'onSaveCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
                'tl_content' => [
                    'list.sorting.child_record_callback' => [[
                        [
                            'service' => 'test.callback_listener',
                            'method' => 'onChildRecordCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
        );
    }

    public function testAddsTheCallbacksByPriority(): void
    {
        $definitionA = new Definition(TestListener::class);

        $definitionA->addTag('contao.callback', [
            'table' => 'tl_page',
            'target' => 'config.onload',
            'priority' => 10,
        ]);

        $definitionB = new Definition(TestListener::class);

        $definitionB->addTag('contao.callback', [
            'table' => 'tl_page',
            'target' => 'config.onload',
            'method' => 'onLoadFirst',
            'priority' => 10,
        ]);

        $definitionB->addTag('contao.callback', [
            'table' => 'tl_page',
            'target' => 'config.onload',
            'method' => 'onLoadSecond',
            'priority' => 100,
        ]);

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
                            [
                                'service' => 'test.callback_listener.a',
                                'method' => 'onLoadCallback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                            [
                                'service' => 'test.callback_listener.b',
                                'method' => 'onLoadFirst',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                        100 => [
                            [
                                'service' => 'test.callback_listener.b',
                                'method' => 'onLoadSecond',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
            $this->getCallbacksFromDefinition($container)[0],
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

    public function testDoesNothingIfThereAreNoCallbacks(): void
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

        $definition->addTag('contao.callback', [
            'table' => 'tl_page',
            'target' => 'tl_page.config.foo',
            'method' => 'onFooCallback',
        ]);

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

        $definition->addTag('contao.callback', [
            'table' => 'tl_page',
            'target' => 'tl_page.config.foo',
            'method' => 'onPrivateCallback',
        ]);

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

        $this->assertSame('setCallbacks', $methodCalls[0][0]);
        $this->assertIsArray($methodCalls[0][1]);

        return $methodCalls[0][1];
    }

    /**
     * Returns the container builder with a dummy "contao.framework" definition.
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->setDefinition(
            'contao.listener.data_container_callback',
            new Definition(DataContainerCallbackListener::class, []),
        );

        return $container;
    }
}

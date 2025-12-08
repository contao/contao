<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\DataContainerCallbackListener;
use Contao\CoreBundle\Fixtures\EventListener\TestListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;

class DataContainerCallbackListenerTest extends TestCase
{
    private DataContainerCallbackListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $framework = $this->createContaoFrameworkStub([System::class => $this->createAdapterStub(['importStatic'])]);

        $this->listener = new DataContainerCallbackListener($framework);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testRegistersCallbacks(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'config.onload_callback' => [[
                        [
                            'service' => 'Test\CallbackListener',
                            'method' => 'onLoadCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
        );

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'config' => [
                    'onload_callback' => [
                        ['Test\CallbackListener', 'onLoadCallback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    public function testRegistersMultipleCallbacks(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'config.onload_callback' => [[
                        [
                            'service' => 'Test\CallbackListener',
                            'method' => 'loadConfigCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                    'fields.title.load_callback' => [[
                        [
                            'service' => 'Test\CallbackListener',
                            'method' => 'loadFieldCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
        );

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'config' => [
                    'onload_callback' => [
                        ['Test\CallbackListener', 'loadConfigCallback'],
                    ],
                ],
                'fields' => [
                    'title' => [
                        'load_callback' => [
                            ['Test\CallbackListener', 'loadFieldCallback'],
                        ],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    #[DataProvider('singletonProvider')]
    public function testRegistersSingletonCallbacks(array $callbacks, array $expected): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(['tl_page' => $callbacks]);

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);
        $this->assertSame($expected, $GLOBALS['TL_DCA']['tl_page']);
    }

    public static function singletonProvider(): iterable
    {
        yield 'handles child_record_callback' => [
            ['list.sorting.child_record_callback' => [[
                [
                    'service' => 'Test\CallbackListener',
                    'method' => 'firstCallback',
                    'closure' => null,
                    'singleton' => null,
                ],
                [
                    'service' => 'Test\CallbackListener',
                    'method' => 'secondCallback',
                    'closure' => null,
                    'singleton' => null,
                ],
            ]]],
            [
                'list' => [
                    'sorting' => [
                        'child_record_callback' => ['Test\CallbackListener', 'firstCallback'],
                    ],
                ],
            ],
        ];

        yield 'uses singleton if attribute is true' => [
            ['fields.foo.barCallback' => [[
                [
                    'service' => 'Test\CallbackListener',
                    'method' => 'firstCallback',
                    'closure' => null,
                    'singleton' => true,
                ],
                [
                    'service' => 'Test\CallbackListener',
                    'method' => 'secondCallback',
                    'closure' => null,
                    'singleton' => null,
                ],
            ]]],
            [
                'fields' => [
                    'foo' => [
                        'barCallback' => ['Test\CallbackListener', 'firstCallback'],
                    ],
                ],
            ],
        ];

        yield 'does not use singleton if attribute is false' => [
            ['list.sorting.child_record_callback' => [[
                [
                    'service' => 'Test\CallbackListener',
                    'method' => 'firstCallback',
                    'closure' => null,
                    'singleton' => false,
                ],
                [
                    'service' => 'Test\CallbackListener',
                    'method' => 'secondCallback',
                    'closure' => null,
                    'singleton' => false,
                ],
            ]]],
            [
                'list' => [
                    'sorting' => [
                        'child_record_callback' => [
                            ['Test\CallbackListener', 'firstCallback'],
                            ['Test\CallbackListener', 'secondCallback'],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('registersClosureProvider')]
    public function testRegistersClosures(string $key, bool|null $closure, bool $expected): void
    {
        $GLOBALS['TL_DCA']['tl_article'] = [];

        $testListener = $this->createMock(TestListener::class);
        $testListener
            ->expects($expected ? $this->once() : $this->never())
            ->method('onClosure')
            ->with($this->isInstanceOf(DataContainer::class))
            ->willReturn('foo')
        ;

        $systemAdapter = $this->createAdapterStub(['importStatic']);
        $systemAdapter
            ->expects($expected ? $this->once() : $this->never())
            ->method('importStatic')
            ->willReturn($testListener)
        ;

        $framework = $this->createContaoFrameworkStub([System::class => $systemAdapter]);

        $listener = new DataContainerCallbackListener($framework);

        $listener->setCallbacks(
            [
                'tl_article' => [
                    'fields.article.'.$key => [[
                        [
                            'service' => TestListener::class,
                            'method' => 'onClosure',
                            'closure' => $closure,
                            'singleton' => true,
                        ],
                    ]],
                ],
            ],
        );

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_article']);

        $listener->onLoadDataContainer('tl_article');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_article']);

        if ($expected) {
            $this->assertIsCallable($GLOBALS['TL_DCA']['tl_article']['fields']['article'][$key]);
            $this->assertSame('foo', $GLOBALS['TL_DCA']['tl_article']['fields']['article'][$key]($this->createMock(DataContainer::class)));
        } else {
            $this->assertSame([TestListener::class, 'onClosure'], $GLOBALS['TL_DCA']['tl_article']['fields']['article'][$key]);
        }
    }

    public static function registersClosureProvider(): iterable
    {
        yield 'detects default callback' => [
            'default',
            null,
            true,
        ];

        yield 'closure is true' => [
            'fooCallback',
            true,
            true,
        ];

        yield 'closure is false' => [
            'default',
            false,
            false,
        ];
    }

    public function testPanelLayoutCallbacks(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'list.sorting.panel_callback.foobar' => [[
                        [
                            'service' => 'Test\CallbackListener',
                            'method' => 'onFoobarCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
        );

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'panel_callback' => [
                            'foobar' => ['Test\CallbackListener', 'onFoobarCallback'],
                        ],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    public function testRegistersCallbacksByPriority(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        100 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priority100Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                        0 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priority0Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                        10 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priority10Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                        -10 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priorityMinus10Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
        );

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'config' => [
                    'onload_callback' => [
                        ['Test\CallbackListener', 'priority100Callback'],
                        ['Test\CallbackListener', 'priority10Callback'],
                        ['Test\CallbackListener', 'priority0Callback'],
                        ['Test\CallbackListener', 'priorityMinus10Callback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    public function testKeepsExistingCallbacksAtPriorityZero(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [
            'config' => [
                'onload_callback' => [
                    ['Test\CallbackListener', 'priority0Callback'],
                    'key' => ['Test\CallbackListener', 'priority0Callback2'],
                ],
            ],
        ];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        10 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priority10Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                        -10 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priorityMinus10Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'config' => [
                    'onload_callback' => [
                        ['Test\CallbackListener', 'priority10Callback'],
                        ['Test\CallbackListener', 'priority0Callback'],
                        'key' => ['Test\CallbackListener', 'priority0Callback2'],
                        ['Test\CallbackListener', 'priorityMinus10Callback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    public function testAddsCallbackWithPriorityZeroAfterExistingOnes(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [
            'config' => [
                'onload_callback' => [
                    ['Test\CallbackListener', 'existingCallback'],
                    'key' => ['Test\CallbackListener', 'existingCallback2'],
                    ['Test\CallbackListener', 'existingCallback3'],
                ],
            ],
        ];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'config.onload_callback' => [[
                        [
                            'service' => 'Test\CallbackListener',
                            'method' => 'newCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'config' => [
                    'onload_callback' => [
                        ['Test\CallbackListener', 'existingCallback'],
                        'key' => ['Test\CallbackListener', 'existingCallback2'],
                        ['Test\CallbackListener', 'existingCallback3'],
                        ['Test\CallbackListener', 'newCallback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    public function testKeepsCallbackWithHighestPriorityForSingletons(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'list.sorting.child_record_callback' => [
                        0 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priority0Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                        10 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priority10Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                        -10 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'priorityMinus10Callback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'child_record_callback' => ['Test\CallbackListener', 'priority10Callback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    public function testNewCallbackWithPriorityZeroOverridesExistingForSingletons(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [
            'list' => [
                'sorting' => [
                    'child_record_callback' => ['Test\CallbackListener', 'existingCallback'],
                ],
            ],
        ];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'list.sorting.child_record_callback' => [[
                        [
                            'service' => 'Test\CallbackListener',
                            'method' => 'newCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'child_record_callback' => ['Test\CallbackListener', 'newCallback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    public function testNewCallbackWithNegativePriorityDoesNotOverrideExistingForSingletons(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [
            'list' => [
                'sorting' => [
                    'child_record_callback' => ['Test\CallbackListener', 'existingCallback'],
                ],
            ],
        ];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'list.sorting.child_record_callback' => [
                        -1 => [
                            [
                                'service' => 'Test\CallbackListener',
                                'method' => 'newCallback',
                                'closure' => null,
                                'singleton' => null,
                            ],
                        ],
                    ],
                ],
            ],
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->assertSame(
            [
                'list' => [
                    'sorting' => [
                        'child_record_callback' => ['Test\CallbackListener', 'existingCallback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page'],
        );
    }

    public function testDoesNothingIfNoCallbacksForTable(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_content' => [
                    'config.onload_callback' => [[
                        [
                            'service' => 'Test\CallbackListener',
                            'method' => 'onLoadCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
        );

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']);
    }

    public function testCreatesTheDataContainerIfNecessary(): void
    {
        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'config.onload_callback' => [[
                        [
                            'service' => 'Test\CallbackListener',
                            'method' => 'onLoadCallback',
                            'closure' => null,
                            'singleton' => null,
                        ],
                    ]],
                ],
            ],
        );

        $this->assertArrayNotHasKey('tl_page', $GLOBALS['TL_DCA'] ?? []);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);
    }
}

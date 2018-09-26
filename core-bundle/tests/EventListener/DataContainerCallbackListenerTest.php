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
use Contao\CoreBundle\Tests\TestCase;

class DataContainerCallbackListenerTest extends TestCase
{
    /**
     * @var DataContainerCallbackListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_DCA']);
        $GLOBALS['TL_DCA'] = [];

        $this->listener = new DataContainerCallbackListener();
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\DataContainerCallbackListener', $this->listener);
    }

    public function testRegistersCallbacks(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'onLoadCallback'],
                        ],
                    ],
                ],
            ]
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
            $GLOBALS['TL_DCA']['tl_page']
        );
    }

    public function testRegistersMultipleCallbacks(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'config.onload_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'loadConfigCallback'],
                        ],
                    ],
                    'fields.title.load_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'loadFieldCallback'],
                        ],
                    ],
                ],
            ]
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
            $GLOBALS['TL_DCA']['tl_page']
        );
    }

    public function testRegistersSingletonCallbacks(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'list.label.label_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'onLoadCallback'],
                        ],
                    ],
                ],
            ]
        );

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);
        $this->assertSame(
            [
                'list' => [
                    'label' => [
                        'label_callback' => ['Test\CallbackListener', 'onLoadCallback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page']
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
                            ['Test\CallbackListener', 'priority100Callback'],
                        ],
                        0 => [
                            ['Test\CallbackListener', 'priority0Callback'],
                        ],
                        10 => [
                            ['Test\CallbackListener', 'priority10Callback'],
                        ],
                        -10 => [
                            ['Test\CallbackListener', 'priorityMinus10Callback'],
                        ],
                    ],
                ],
            ]
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
            $GLOBALS['TL_DCA']['tl_page']
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
                            ['Test\CallbackListener', 'priority10Callback'],
                        ],
                        -10 => [
                            ['Test\CallbackListener', 'priorityMinus10Callback'],
                        ],
                    ],
                ],
            ]
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
            $GLOBALS['TL_DCA']['tl_page']
        );
    }

    public function testAddsCallbackWithPriorityZeroBeforeExistingOnes(): void
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
                    'config.onload_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'newCallback'],
                        ],
                    ],
                ],
            ]
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);
        $this->assertSame(
            [
                'config' => [
                    'onload_callback' => [
                        ['Test\CallbackListener', 'newCallback'],
                        ['Test\CallbackListener', 'existingCallback'],
                        'key' => ['Test\CallbackListener', 'existingCallback2'],
                        ['Test\CallbackListener', 'existingCallback3'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page']
        );
    }

    public function testKeepsCallbackWithHighestPriorityForSingletons(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'list.label.label_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'priority0Callback'],
                        ],
                        10 => [
                            ['Test\CallbackListener', 'priority10Callback'],
                        ],
                        -10 => [
                            ['Test\CallbackListener', 'priorityMinus10Callback'],
                        ],
                    ],
                ],
            ]
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);
        $this->assertSame(
            [
                'list' => [
                    'label' => [
                        'label_callback' => ['Test\CallbackListener', 'priority10Callback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page']
        );
    }

    public function testNewCallbackWithPriorityZeroOverridesExistingForSingletons(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [
            'list' => [
                'label' => [
                    'label_callback' => ['Test\CallbackListener', 'existingCallback'],
                ],
            ],
        ];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'list.label.label_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'newCallback'],
                        ],
                    ],
                ],
            ]
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);
        $this->assertSame(
            [
                'list' => [
                    'label' => [
                        'label_callback' => ['Test\CallbackListener', 'newCallback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page']
        );
    }

    public function testNewCallbackWithNegativePriorityDoesNotOverrideExistingForSingletons(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [
            'list' => [
                'label' => [
                    'label_callback' => ['Test\CallbackListener', 'existingCallback'],
                ],
            ],
        ];

        $this->listener->setCallbacks(
            [
                'tl_page' => [
                    'list.label.label_callback' => [
                        -1 => [
                            ['Test\CallbackListener', 'newCallback'],
                        ],
                    ],
                ],
            ]
        );

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);
        $this->assertSame(
            [
                'list' => [
                    'label' => [
                        'label_callback' => ['Test\CallbackListener', 'existingCallback'],
                    ],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_page']
        );
    }

    public function testDoesNothingIfNoCallbacksForTable(): void
    {
        $GLOBALS['TL_DCA']['tl_page'] = [];

        $this->listener->setCallbacks(
            [
                'tl_content' => [
                    'config.onload_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'onLoadCallback'],
                        ],
                    ],
                ],
            ]
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
                    'config.onload_callback' => [
                        0 => [
                            ['Test\CallbackListener', 'onLoadCallback'],
                        ],
                    ],
                ],
            ]
        );

        $this->assertArrayNotHasKey('tl_page', $GLOBALS['TL_DCA']);

        $this->listener->onLoadDataContainer('tl_page');

        $this->assertNotEmpty($GLOBALS['TL_DCA']['tl_page']);
    }
}

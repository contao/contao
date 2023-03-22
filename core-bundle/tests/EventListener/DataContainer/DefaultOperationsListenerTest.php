<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\EventListener\DataContainer\DefaultOperationsListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Security;

class DefaultOperationsListenerTest extends TestCase
{
    private DefaultOperationsListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_DCA']);

        $security = $this->createMock(Security::class);
        $connection = $this->createMock(Connection::class);

        $this->listener = new DefaultOperationsListener($security, $connection);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    public function testAddsDefaultOperations(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'delete', 'show'], array_keys($operations));
        $this->assertOperation($operations['edit'], 'act=edit', 'edit.svg', true);
        $this->assertOperation($operations['copy'], 'act=copy', 'copy.svg', true);
        $this->assertOperation($operations['delete'], 'act=delete', 'delete.svg', true);
        $this->assertOperation($operations['show'], 'act=show', 'show.svg', false);
    }

    public function testAddsChildrenOperationsWithChildTable(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'ctable' => ['tl_bar'],
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'children', 'copy', 'delete', 'show'], array_keys($operations));
        $this->assertOperation($operations['edit'], 'act=edit', 'edit.svg', true);
        $this->assertOperation($operations['children'], 'table=tl_bar', 'children.svg', false);
        $this->assertOperation($operations['copy'], 'act=copy', 'copy.svg', true);
        $this->assertOperation($operations['delete'], 'act=delete', 'delete.svg', true);
        $this->assertOperation($operations['show'], 'act=show', 'show.svg', false);
    }

    public function testAddsOperationsWithParentTable(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'ptable' => ['tl_bar'],
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_PARENT,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'cut', 'delete', 'show'], array_keys($operations));
        $this->assertOperation($operations['edit'], 'act=edit', 'edit.svg', true);
        $this->assertOperation($operations['copy'], 'act=paste&amp;mode=copy', 'copy.svg', true);
        $this->assertOperation($operations['cut'], 'act=paste&amp;mode=cut', 'cut.svg', true);
        $this->assertOperation($operations['delete'], 'act=delete', 'delete.svg', true);
        $this->assertOperation($operations['show'], 'act=show', 'show.svg', false);
    }

    public function testAddsOperationsInTreeMode(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_TREE,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'copyChilds', 'cut', 'delete', 'show'], array_keys($operations));
        $this->assertOperation($operations['edit'], 'act=edit', 'edit.svg', true);
        $this->assertOperation($operations['copy'], 'act=paste&amp;mode=copy', 'copy.svg', true);
        $this->assertOperation($operations['copyChilds'], 'act=paste&amp;mode=copy&amp;childs=1', 'copychilds.svg', true);
        $this->assertOperation($operations['cut'], 'act=paste&amp;mode=cut', 'cut.svg', true);
        $this->assertOperation($operations['delete'], 'act=delete', 'delete.svg', true);
        $this->assertOperation($operations['show'], 'act=show', 'show.svg', false);
    }

    public function testAddsToggleOperationIfThereIsOneToggleField(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
            'fields' => [
                'featured' => [
                    'inputType' => 'checkbox',
                ],
                'published' => [
                    'inputType' => 'checkbox',
                    'toggle' => true,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'delete', 'toggle', 'show'], array_keys($operations));
        $this->assertOperation($operations['edit'], 'act=edit', 'edit.svg', true);
        $this->assertOperation($operations['copy'], 'act=copy', 'copy.svg', true);
        $this->assertOperation($operations['delete'], 'act=delete', 'delete.svg', true);
        $this->assertOperation($operations['toggle'], 'act=toggle&amp;field=published', 'visible.svg', true);
        $this->assertOperation($operations['show'], 'act=show', 'show.svg', false);
    }

    public function testAddsToggleOperationIfThereIsOneReverseToggleField(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
            'fields' => [
                'featured' => [
                    'inputType' => 'checkbox',
                    'reverseToggle' => true,
                ],
                'published' => [
                    'inputType' => 'checkbox',
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'delete', 'toggle', 'show'], array_keys($operations));
        $this->assertOperation($operations['edit'], 'act=edit', 'edit.svg', true);
        $this->assertOperation($operations['copy'], 'act=copy', 'copy.svg', true);
        $this->assertOperation($operations['delete'], 'act=delete', 'delete.svg', true);
        $this->assertOperation($operations['toggle'], 'act=toggle&amp;field=featured', 'visible.svg', true);
        $this->assertOperation($operations['show'], 'act=show', 'show.svg', false);
    }

    public function testDoesNotAddToggleOperationIfThereAreMultipleToggleField(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
            'fields' => [
                'featured' => [
                    'inputType' => 'checkbox',
                    'toggle' => true,
                ],
                'published' => [
                    'inputType' => 'checkbox',
                    'toggle' => true,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'delete', 'show'], array_keys($operations));
        $this->assertOperation($operations['edit'], 'act=edit', 'edit.svg', true);
        $this->assertOperation($operations['copy'], 'act=copy', 'copy.svg', true);
        $this->assertOperation($operations['delete'], 'act=delete', 'delete.svg', true);
        $this->assertOperation($operations['show'], 'act=show', 'show.svg', false);
    }

    public function testExpandsNamedOperations(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
                'operations' => [
                    'edit',
                    'foo' => [
                        'href' => 'foo=bar',
                        'icon' => 'foo.svg',
                    ],
                    'delete',
                    'show',
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'foo', 'delete', 'show'], array_keys($operations));
    }

    public function testManuallySortOperations(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
                'operations' => [
                    'delete',
                    'edit',
                    'show',
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['delete', 'edit', 'show'], array_keys($operations));
    }

    public function testAppendsCustomOperationsToDefaults(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
                'operations' => [
                    'foo' => [
                        'href' => 'foo=bar',
                        'icon' => 'foo.svg',
                    ],
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'delete', 'show', 'foo'], array_keys($operations));
    }

    public function testKeepsPositionForNamedOperations(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
                'operations' => [
                    'edit',
                    'foo' => [
                        'href' => 'foo=bar',
                        'icon' => 'foo.svg',
                    ],
                    'show',
                    'delete',
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'foo', 'show', 'delete'], array_keys($operations));
    }

    public function testDoesNotAppendsIfOneOperationHasADefaultName(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
                'operations' => [
                    'foo' => [
                        'href' => 'foo=bar',
                        'icon' => 'foo.svg',
                    ],
                    'delete' => [
                        'href' => 'act=delete',
                        'icon' => 'delete.svg',
                    ],
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['foo', 'delete'], array_keys($operations));
    }

    public function testDoesNotAddEditOperationIfTableIsNotEditable(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'notEditable' => true,
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['copy', 'delete', 'show'], array_keys($operations));
    }

    public function testDoesNotAddCopyOperationIfTableIsNotCopyable(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'notCopyable' => true,
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'delete', 'show'], array_keys($operations));
    }

    public function testDoesNotAddCopyOperationIfTableIsClosed(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'closed' => true,
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'delete', 'show'], array_keys($operations));
    }

    public function testDoesNotAddCutOperationIfTableIsNotSortable(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'ptable' => 'tl_bar',
                'notSortable' => true,
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'delete', 'show'], array_keys($operations));
    }

    public function testDoesNotAddDeleteOperationIfTableIsNotDeletable(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'notDeletable' => true,
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['edit', 'copy', 'show'], array_keys($operations));
    }

    public function testAlwaysAddsChildrenAndShowOperation(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'closed' => true,
                'notCreatable' => true,
                'notEditable' => true,
                'notCopyable' => true,
                'notSortable' => true,
                'notDeletable' => true,
                'ctable' => ['tl_bar'],
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        ($this->listener)('tl_foo');

        $this->assertArrayHasKey('operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'];

        $this->assertSame(['children', 'show'], array_keys($operations));
    }

    private function assertOperation(array $operation, string $href, string $icon, bool $hasCallback): void
    {
        $this->assertArrayHasKey('href', $operation);
        $this->assertSame($operation['href'], $href);
        $this->assertArrayHasKey('icon', $operation);
        $this->assertSame($operation['icon'], $icon);

        if ($hasCallback) {
            $this->assertArrayHasKey('button_callback', $operation);
            $this->assertIsCallable($operation['button_callback']);

            $ref = new \ReflectionFunction($operation['button_callback']);

            $this->assertSame(1, $ref->getNumberOfParameters());

            /** @var \ReflectionNamedType $type */
            $type = $ref->getParameters()[0]->getType();
            $this->assertSame(DataContainerOperation::class, $type->getName());
        } else {
            $this->assertArrayNotHasKey('button_callback', $operation);
        }
    }
}

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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\AbstractAction;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;

class DefaultOperationsListenerTest extends TestCase
{
    /**
     * @var Security&MockObject
     */
    private Security $security;

    private DefaultOperationsListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_DCA']);

        $this->security = $this->createMock(Security::class);
        $this->listener = new DefaultOperationsListener($this->security, $this->createMock(Connection::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    public function testAddsDefaultOperations(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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

    /**
     * @dataProvider checkPermissionsProvider
     */
    public function testCheckPermissions(string $name, string $actionClass, array $record, array $dca = [], array|null $newRecord = null): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = $dca;

        ($this->listener)('tl_foo');

        $operation = $GLOBALS['TL_DCA']['tl_foo']['list']['operations'][$name];

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(
                ContaoCorePermissions::DC_PREFIX.'tl_foo',
                $this->callback(
                    function (AbstractAction $action) use ($actionClass, $record, $newRecord) {
                        $this->assertInstanceOf($actionClass, $action);
                        $this->assertSame('tl_foo', $action->getDataSource());

                        if ($action instanceof UpdateAction || $action instanceof DeleteAction) {
                            $this->assertSame($record, $action->getCurrent());
                        }

                        if ($action instanceof CreateAction) {
                            $this->assertSame($newRecord ?? $record, $action->getNew());
                        }

                        return true;
                    },
                ),
            )
            ->willReturn(true)
        ;

        $config = new DataContainerOperation($name, $operation, $record, $this->createMock(DataContainer::class));
        $operation['button_callback']($config);
    }

    public function checkPermissionsProvider(): \Generator
    {
        yield 'edit operation' => [
            'edit',
            UpdateAction::class,
            ['id' => 15, 'foo' => 'bar'],
        ];

        yield 'copy operation' => [
            'copy',
            CreateAction::class,
            ['id' => 15, 'pid' => 42, 'foo' => 'bar'],
            [],
            ['pid' => 42, 'foo' => 'bar', 'tstamp' => 0],
        ];

        yield 'delete operation' => [
            'delete',
            DeleteAction::class,
            ['id' => 15, 'foo' => 'bar'],
        ];

        yield 'copy operation in tree mode' => [
            'copy',
            CreateAction::class,
            ['id' => 15, 'pid' => 0, 'foo' => 'bar'],
            ['list' => ['sorting' => ['mode' => DataContainer::MODE_TREE]]],
            ['foo' => 'bar', 'tstamp' => 0],
        ];

        yield 'copy operation with parent table' => [
            'copy',
            CreateAction::class,
            ['id' => 15, 'pid' => 42, 'sorting' => 128, 'foo' => 'bar'],
            ['config' => ['ptable' => 'tl_bar']],
            ['foo' => 'bar', 'tstamp' => 0],
        ];

        yield 'copyChilds operation in tree mode' => [
            'copyChilds',
            CreateAction::class,
            ['id' => 15, 'pid' => 42, 'sorting' => 128, 'foo' => 'bar'],
            ['list' => ['sorting' => ['mode' => DataContainer::MODE_TREE]]],
            ['foo' => 'bar', 'tstamp' => 0],
        ];

        yield 'cut operation in tree mode' => [
            'cut',
            UpdateAction::class,
            ['id' => 15, 'foo' => 'bar'],
            ['list' => ['sorting' => ['mode' => DataContainer::MODE_TREE]]],
        ];

        yield 'cut operation with parent table' => [
            'cut',
            UpdateAction::class,
            ['id' => 15, 'foo' => 'bar'],
            ['config' => ['ptable' => 'tl_bar']],
        ];

        yield 'toggle operation' => [
            'toggle',
            UpdateAction::class,
            ['id' => 15, 'foo' => 'bar'],
            ['fields' => ['foo' => ['toggle' => true]]],
        ];

        yield 'reverse toggle operation' => [
            'toggle',
            UpdateAction::class,
            ['id' => 15, 'foo' => 'bar'],
            ['fields' => ['foo' => ['reverseToggle' => true]]],
        ];
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

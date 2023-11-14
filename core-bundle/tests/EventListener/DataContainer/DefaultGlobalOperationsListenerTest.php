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
use Contao\CoreBundle\EventListener\DataContainer\DefaultGlobalOperationsListener;
use Contao\CoreBundle\EventListener\DataContainer\DefaultOperationsListener;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\AbstractAction;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\DC_Folder;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;

class DefaultGlobalOperationsListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_DCA']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    /**
     * @dataProvider editAllOperationProvider
     */
    public function testAddsEditAllOperation(bool $closed, bool $notEditable, bool $notCopyable, bool $hasOperation): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
                'closed' => $closed,
                'notEditable' => $notEditable,
                'notCopyable' => $notCopyable,
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_SORTED,
                ],
            ],
        ];

        (new DefaultGlobalOperationsListener())('tl_foo');

        $this->assertArrayHasKey('global_operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['global_operations'];

        if ($hasOperation) {
            $this->assertArrayHasKey('all', $operations);
        } else {
            $this->assertArrayNotHasKey('all', $operations);
        }
    }

    public function editAllOperationProvider(): \Generator
    {
        yield 'has operation if DCA is editable' => [false, false, false, true];

        yield 'has operation if records can be added' => [true, false, false, true];

        yield 'has operation if records can be edited' => [true, false, true, true];

        yield 'has operation if records can be copied' => [false, false, true, true];

        yield 'does not have operation if records cannot be created or edited' => [true, true, false, false];

        yield 'does not have operation if records cannot be edited or copied' => [false, true, true, false];
    }

    /**
     * @dataProvider toggleNodesOperationProvider
     */
    public function testAddsToggleNodesOperation(string $driver, int $mode, bool $hasOperation): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => $driver,
            ],
            'list' => [
                'sorting' => [
                    'mode' => $mode,
                ],
            ],
        ];

        (new DefaultGlobalOperationsListener())('tl_foo');

        $this->assertArrayHasKey('global_operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['global_operations'];

        if ($hasOperation) {
            $this->assertArrayHasKey('toggleNodes', $operations);
        } else {
            $this->assertArrayNotHasKey('toggleNodes', $operations);
        }
    }

    public function toggleNodesOperationProvider(): \Generator
    {
        yield 'does not have toggleNodes in unsorted mode' => [DC_Table::class, DataContainer::MODE_UNSORTED, false];

        yield 'does not have toggleNodes in sorted mode' => [DC_Table::class, DataContainer::MODE_SORTED, false];

        yield 'does not have toggleNodes in sortable mode' => [DC_Table::class, DataContainer::MODE_SORTABLE, false];

        yield 'does not have toggleNodes in sorted parent mode' => [DC_Table::class, DataContainer::MODE_SORTED_PARENT, false];

        yield 'does not have toggleNodes in parent mode' => [DC_Table::class, DataContainer::MODE_PARENT, false];

        yield 'has toggleNodes in tree mode' => [DC_Table::class, DataContainer::MODE_TREE, true];

        yield 'has toggleNodes in extended tree mode' => [DC_Table::class, DataContainer::MODE_TREE_EXTENDED, true];

        yield 'has toggleNodes for DC_Folder' => [DC_Folder::class, 0, true];
    }

    public function testDoesNotAddOperationsForUnknownDataContainer(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => 'FoobarClass',
            ],
            'list' => [
                'sorting' => [
                    'mode' => DataContainer::MODE_TREE,
                ],
            ],
        ];

        (new DefaultGlobalOperationsListener())('tl_foo');

        $this->assertArrayHasKey('global_operations', $GLOBALS['TL_DCA']['tl_foo']['list']);
        $operations = $GLOBALS['TL_DCA']['tl_foo']['list']['global_operations'];

        $this->assertArrayNotHasKey('all', $operations);
    }
}

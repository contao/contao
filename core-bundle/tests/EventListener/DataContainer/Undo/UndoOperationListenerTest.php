<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer\Undo;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\EventListener\DataContainer\Undo\UndoOperationListener;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class UndoOperationListenerTest extends TestCase
{
    /**
     * @dataProvider undoOperationListenerProvider
     */
    public function testUndoOperationListener(array $data, array $isGranted): void
    {
        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn(['data' => serialize($data)])
        ;

        $operation
            ->expects(\in_array(false, $isGranted, true) ? $this->once() : $this->never())
            ->method('disable')
        ;

        $calls = [];

        foreach ($data as $table => $rows) {
            foreach ($rows as $row) {
                $calls[] = [
                    ContaoCorePermissions::DC_PREFIX.$table,
                    $this->callback(static fn (CreateAction $action) => $table === $action->getDataSource() && $row === $action->getNew()),
                ];
            }
        }

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(\count($isGranted)))
            ->method('isGranted')
            ->withConsecutive(...$calls)
            ->willReturnOnConsecutiveCalls(...$isGranted)
        ;

        $listener = new UndoOperationListener($security);
        $listener($operation);
    }

    public function undoOperationListenerProvider(): \Generator
    {
        yield [
            ['tl_foo' => [['id' => 42]]],
            [true],
        ];

        yield [
            ['tl_foo' => [['id' => 42], ['id' => 43]]],
            [true, true],
        ];

        yield [
            ['tl_foo' => [['id' => 42]], 'tl_bar' => [['id' => 43]]],
            [true, true],
        ];

        yield [
            ['tl_foo' => [['id' => 42]]],
            [false],
        ];

        yield [
            ['tl_foo' => [['id' => 42], ['id' => 43]]],
            [true, false],
        ];

        yield [
            ['tl_foo' => [['id' => 42]], 'tl_bar' => [['id' => 43]]],
            [true, false],
        ];
    }
}

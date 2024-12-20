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
    public function testUndoOperationIsGranted(): void
    {
        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn(['fromTable' => 'tl_foo', 'data' => serialize(['tl_foo' => [['id' => 42]]])])
        ;

        $operation
            ->expects($this->never())
            ->method('disable')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with(
                ContaoCorePermissions::DC_PREFIX.'tl_foo',
                $this->callback(static fn ($action) => $action instanceof CreateAction && 'tl_foo' === $action->getDataSource()),
            )
            ->willReturn(true)
        ;

        $listener = new UndoOperationListener($security);
        $listener($operation);
    }

    public function testUndoOperationIsDisabledIfIsNotGranted(): void
    {
        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn(['fromTable' => 'tl_bar', 'data' => serialize(['tl_bar' => [['id' => 42]]])])
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with(
                ContaoCorePermissions::DC_PREFIX.'tl_bar',
                $this->callback(static fn ($action) => $action instanceof CreateAction && 'tl_bar' === $action->getDataSource()),
            )
            ->willReturn(false)
        ;

        $listener = new UndoOperationListener($security);
        $listener($operation);
    }

    public function testUndoOperationIsDisabledIfRecordIsMissing(): void
    {
        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn(['fromTable' => 'tl_bar', 'data' => serialize([])])
        ;

        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $listener = new UndoOperationListener($security);
        $listener($operation);
    }
}

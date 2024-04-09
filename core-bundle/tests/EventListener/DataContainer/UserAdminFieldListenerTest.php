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

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\DataContainer\UserAdminFieldListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Symfony\Bundle\SecurityBundle\Security;

class UserAdminFieldListenerTest extends TestCase
{
    public function testUnsetAdminFieldForRegularUsers(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false)
        ;

        $security
            ->expects($this->never())
            ->method('getUser')
        ;

        $dataContainer = $this->createMock(DataContainer::class);

        $listener = new UserAdminFieldListener($security);
        $palette = $listener('foo,bar;{admin_legend},admin;disable', $dataContainer);

        $this->assertSame('foo,bar;disable', $palette);
    }

    public function testUnsetsFieldsForAdminWhenEditingOwnRecord(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 42]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true)
        ;

        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $dataContainer = $this->mockClassWithProperties(DataContainer::class, ['id' => 42]);

        $listener = new UserAdminFieldListener($security);
        $palette = $listener('foo,bar;{admin_legend},admin;disable,start,stop', $dataContainer);

        $this->assertSame('foo,bar', $palette);
    }

    public function testKeepsAdminFieldForAdminWhenEditingOtherRecords(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 42]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true)
        ;

        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $dataContainer = $this->mockClassWithProperties(DataContainer::class, ['id' => 1]);

        $listener = new UserAdminFieldListener($security);
        $palette = $listener('foo,bar;{admin_legend},admin;disable,start,stop', $dataContainer);

        $this->assertSame('foo,bar;{admin_legend},admin;disable,start,stop', $palette);
    }
}

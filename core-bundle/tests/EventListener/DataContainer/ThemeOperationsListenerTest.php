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
use Contao\CoreBundle\EventListener\DataContainer\ThemeOperationsListener;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Symfony\Bundle\SecurityBundle\Security;

class ThemeOperationsListenerTest extends TestCase
{
    /**
     * @dataProvider themeOperationsProvider
     */
    public function testThemeOperations(string|null $href, string|null $attribute, bool $isGranted): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($attribute ? $this->once() : $this->never())
            ->method('isGranted')
            ->with($attribute)
            ->willReturn($isGranted)
        ;

        $operation = new DataContainerOperation('foo', ['href' => $href], [], $this->createMock(DataContainer::class));

        $listener = new ThemeOperationsListener($security);
        $listener($operation);

        if (!$isGranted) {
            $this->assertArrayNotHasKey('href', $operation);
        } else {
            $this->assertSame($href, $operation['href']);
        }
    }

    public static function themeOperationsProvider(): iterable
    {
        yield [
            'table=tl_module',
            ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULES,
            true,
        ];

        yield [
            'table=tl_module',
            ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULES,
            false,
        ];

        yield [
            'table=tl_layout',
            ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS,
            true,
        ];

        yield [
            'table=tl_layout',
            ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS,
            false,
        ];

        yield [
            'table=tl_image_size',
            ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZES,
            true,
        ];

        yield [
            'table=tl_image_size',
            ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZES,
            false,
        ];

        yield [
            'foo=bar',
            '',
            true,
        ];

        yield [
            null,
            '',
            true,
        ];
    }
}

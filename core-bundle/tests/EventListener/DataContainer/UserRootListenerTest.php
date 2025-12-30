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
use Contao\CoreBundle\EventListener\DataContainer\UserRootListener;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Folder;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserRootListenerTest extends TestCase
{
    public function testDoesNotRegisterCallbacksIfDriverIsNotTable(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => DC_Folder::class,
            ],
        ];

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->never())
            ->method('getUser')
        ;

        $requestStack = $this->createStub(RequestStack::class);
        $connection = $this->createStub(Connection::class);

        $listener = new UserRootListener($security, $requestStack, $connection);
        $listener('tl_foo');

        $this->assertArrayNotHasKey('onload_callback', $GLOBALS['TL_DCA']['tl_foo']['config']);
        $this->assertArrayNotHasKey('oncreate_callback', $GLOBALS['TL_DCA']['tl_foo']['config']);
        $this->assertArrayNotHasKey('oncopy_callback', $GLOBALS['TL_DCA']['tl_foo']['config']);

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotRegisterCallbacksIfUserIsNotBackendUser(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
            ],
        ];

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $requestStack = $this->createStub(RequestStack::class);
        $connection = $this->createStub(Connection::class);

        $listener = new UserRootListener($security, $requestStack, $connection);
        $listener('tl_foo');

        $this->assertArrayNotHasKey('onload_callback', $GLOBALS['TL_DCA']['tl_foo']['config']);
        $this->assertArrayNotHasKey('oncreate_callback', $GLOBALS['TL_DCA']['tl_foo']['config']);
        $this->assertArrayNotHasKey('oncopy_callback', $GLOBALS['TL_DCA']['tl_foo']['config']);

        unset($GLOBALS['TL_DCA']);
    }

    public function testOnlyRegistersPermissionFieldCallbackIfUserIsAdmin(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
            ],
        ];

        $user = $this->createStub(BackendUser::class);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true)
        ;

        $requestStack = $this->createStub(RequestStack::class);
        $connection = $this->createStub(Connection::class);

        $listener = new UserRootListener($security, $requestStack, $connection);
        $listener('tl_foo');

        $this->assertCount(1, $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback']);
        $this->assertArrayNotHasKey('oncreate_callback', $GLOBALS['TL_DCA']['tl_foo']['config']);
        $this->assertArrayNotHasKey('oncopy_callback', $GLOBALS['TL_DCA']['tl_foo']['config']);

        unset($GLOBALS['TL_DCA']);
    }

    public function testRegistersAllCallbacksForNonAdmins(): void
    {
        $this->registerCallbacks();

        $this->assertCount(2, $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback']);
        $this->assertCount(1, $GLOBALS['TL_DCA']['tl_foo']['config']['oncreate_callback']);
        $this->assertCount(1, $GLOBALS['TL_DCA']['tl_foo']['config']['oncopy_callback']);

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotInjectPermissionFieldWithoutUserRoot(): void
    {
        $this->registerCallbacks();

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback'][1] ?? null;
        $this->assertIsCallable($callback);

        $callback('tl_foo', $this->createStub(BackendUser::class));

        $this->assertArrayNotHasKey('_permissions', $GLOBALS['TL_DCA']['tl_foo']['fields'] ?? []);
    }

    public function testDoesNotInjectPermissionFieldIfUserCannotAccessModules(): void
    {
        $security = $this->mockSecurity(false);
        $security
            ->expects($this->exactly(3))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ADMIN', null, null, true],
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'user', false],
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'group', false],
            ])
        ;

        $this->registerCallbacks($security);

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback'][0] ?? null;
        $this->assertIsCallable($callback);

        $callback('tl_foo', $this->createStub(BackendUser::class));

        $this->assertArrayNotHasKey('_permissions', $GLOBALS['TL_DCA']['tl_foo']['fields'] ?? []);
    }

    public function testDoesNotInjectPermissionFieldIfUserCannotAccessFields(): void
    {
        $security = $this->mockSecurity(false);
        $security
            ->expects($this->exactly(5))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ADMIN', null, null, true],
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'user', true],
                [ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_user::foobars', false],
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'group', true],
                [ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_user_group::foobars', false],
            ])
        ;

        $this->registerCallbacks($security);

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback'][0] ?? null;
        $this->assertIsCallable($callback);

        $callback('tl_foo', $this->createStub(BackendUser::class));

        $this->assertArrayNotHasKey('_permissions', $GLOBALS['TL_DCA']['tl_foo']['fields'] ?? []);
    }

    public function testInjectsPermissionField(): void
    {
        $security = $this->mockSecurity(false);
        $security
            ->expects($this->exactly(5))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_ADMIN', null, null, true],
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'user', true],
                [ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_user::foobars', true],
                [ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'group', true],
                [ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_user_group::foobars', true],
            ])
        ;

        $this->registerCallbacks($security);

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback'][0] ?? null;
        $this->assertIsCallable($callback);

        $callback('tl_foo', $this->createStub(BackendUser::class));

        $this->assertArrayHasKey('_permissions', $GLOBALS['TL_DCA']['tl_foo']['fields'] ?? []);
        $this->assertCount(1, $GLOBALS['TL_DCA']['tl_foo']['config']['onpalette_callback']);
    }

    public function testDoesNotFilterRecordsWithoutUserRoot(): void
    {
        $this->registerCallbacks();

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback'][0] ?? null;
        $this->assertIsCallable($callback);
        $callback();

        $this->assertArrayNotHasKey('root', $GLOBALS['TL_DCA']['tl_foo']['list']['sorting'] ?? []);
    }

    public function testFiltersRecordsWithoutAccess(): void
    {
        $this->registerCallbacks();

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback'][0] ?? null;
        $this->assertIsCallable($callback);
        $callback();

        $this->assertArrayHasKey('root', $GLOBALS['TL_DCA']['tl_foo']['list']['sorting'] ?? []);
        $this->assertSame([0], $GLOBALS['TL_DCA']['tl_foo']['list']['sorting']['root']);
    }

    public function testFiltersRecords(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, [
            'foobars' => [1, 2, 3],
        ]);

        $this->registerCallbacks($this->mockSecurity(true, $user));

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['onload_callback'][0] ?? null;
        $this->assertIsCallable($callback);
        $callback();

        $this->assertArrayHasKey('root', $GLOBALS['TL_DCA']['tl_foo']['list']['sorting'] ?? []);
        $this->assertSame([1, 2, 3], $GLOBALS['TL_DCA']['tl_foo']['list']['sorting']['root']);
    }

    public function testDoesNotAdjustsPermissionsIfIdIsAlreadyEnabled(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, [
            'foobars' => [1, 2, 3],
        ]);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->never())
            ->method($this->anything())
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->registerCallbacks($this->mockSecurity(true, $user), $requestStack, $connection);

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['oncreate_callback'][0] ?? null;
        $this->assertIsCallable($callback);
        $callback(null, 1);
    }

    public function testDoesNotAdjustsPermissionsIfIdIsNotANewRecord(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, [
            'foobars' => [1, 2, 3],
        ]);

        $requestStack = $this->mockRequestStackWithSession([]);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->registerCallbacks($this->mockSecurity(true, $user), $requestStack, $connection);

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['oncreate_callback'][0] ?? null;
        $this->assertIsCallable($callback);
        $callback(null, 42);
    }

    public function testDoesNotAdjustPermissionsIfUpdateActionIsDenied(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, [
            'foobars' => [1, 2, 3],
            'inherit' => 'group',
        ]);

        $security = $this->mockSecurity(false, $user);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnCallback(static fn (string $attribute, $subject): bool => match ($attribute) {
                'ROLE_ADMIN' => false,
                ContaoCorePermissions::DC_PREFIX.'tl_user_group' => false,
            })
        ;

        $requestStack = $this->mockRequestStackWithSession(['tl_foo' => [42]]);

        $connection = $this->mockConnection([['id' => 1]]);
        $connection
            ->expects($this->never())
            ->method('update')
        ;

        $this->registerCallbacks($security, $requestStack, $connection);

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['oncreate_callback'][0] ?? null;
        $this->assertIsCallable($callback);
        $callback(null, 42);
    }

    public function testAdjustsGroupPermissions(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, [
            'foobars' => [1, 2, 3],
            'inherit' => 'group',
        ]);

        $security = $this->mockSecurity(false, $user);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnCallback(static fn (string $attribute, $subject): bool => match ($attribute) {
                'ROLE_ADMIN' => false,
                ContaoCorePermissions::DC_PREFIX.'tl_user_group' => $subject instanceof UpdateAction,
            })
        ;

        $requestStack = $this->mockRequestStackWithSession(['tl_foo' => [42]]);

        $connection = $this->mockConnection([['id' => 1, 'foobars' => serialize([1, 2, 3])]]);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with('tl_user_group', ['foobars' => serialize([1, 2, 3, 42])], ['id' => 1])
        ;

        $this->registerCallbacks($security, $requestStack, $connection);

        $GLOBALS['TL_DCA']['tl_foo']['config']['userRoot'] = 'foobars';

        $callback = $GLOBALS['TL_DCA']['tl_foo']['config']['oncreate_callback'][0] ?? null;
        $this->assertIsCallable($callback);
        $callback(null, 42);
    }

    private function registerCallbacks(Security|null $security = null, RequestStack|null $requestStack = null, Connection|null $connection = null): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
            ],
        ];

        if (null === $security) {
            $security = $this->mockSecurity();
        }

        $requestStack ??= $this->createStub(RequestStack::class);
        $connection ??= $this->createStub(Connection::class);

        $listener = new UserRootListener($security, $requestStack, $connection);
        $listener('tl_foo');
    }

    private function mockSecurity(bool $isGranted = true, BackendUser|null $user = null): Security&MockObject
    {
        $user ??= $this->createStub(BackendUser::class);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        if ($isGranted) {
            $security
                ->expects($this->once())
                ->method('isGranted')
                ->with('ROLE_ADMIN')
                ->willReturn(false)
            ;
        }

        return $security;
    }

    private function mockRequestStackWithSession(array $newRecords): RequestStack
    {
        $sessionBag = $this->createMock(AttributeBagInterface::class);
        $sessionBag
            ->expects($this->once())
            ->method('get')
            ->with('new_records')
            ->willReturn($newRecords)
        ;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('getBag')
            ->with('contao_backend')
            ->willReturn($sessionBag)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        return $requestStack;
    }

    private function mockConnection(array $groupIds): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($groupIds)
        ;

        return $connection;
    }
}

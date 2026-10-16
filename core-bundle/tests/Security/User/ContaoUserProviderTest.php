<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\User;

use Contao\CoreBundle\DataContainer\VirtualFieldsHandler;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\BackendUser;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Security\User\FrontendUser;
use Contao\CoreBundle\Security\User\UserFactoryInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Contao\UserModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ContaoUserProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $this->createStub(Connection::class));
        System::setContainer($container);

        $GLOBALS['TL_MODELS']['tl_user'] = UserModel::class;
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        unset($GLOBALS['TL_MODELS']);

        parent::tearDown();
    }

    public function testLoadsUsersByUsername(): void
    {
        $user = $this->createStub(BackendUser::class);
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_user WHERE username=?', ['foobar'])
            ->willReturn(['id' => 1, 'username' => 'foobar'])
        ;

        $userFactory = $this->createMock(UserFactoryInterface::class);
        $userFactory
            ->method('getTable')
            ->willReturn('tl_user')
        ;

        $userFactory
            ->expects($this->once())
            ->method('create')
            ->with(['id' => 1, 'username' => 'foobar'])
            ->willReturn($user)
        ;

        $virtualFieldsHandler = $this->createStub(VirtualFieldsHandler::class);
        $virtualFieldsHandler
            ->method('expandFields')
            ->willReturnArgument(0)
        ;

        $provider = $this->getProvider(null, $connection, $userFactory, $virtualFieldsHandler);

        $this->assertSame($user, $provider->loadUserByIdentifier('foobar'));
    }

    public function testLoadsUsersById(): void
    {
        $user = $this->createStub(BackendUser::class);
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_user WHERE id=?', [1])
            ->willReturn(['id' => 1, 'username' => 'foobar'])
        ;

        $userFactory = $this->createMock(UserFactoryInterface::class);
        $userFactory
            ->method('getTable')
            ->willReturn('tl_user')
        ;

        $userFactory
            ->expects($this->once())
            ->method('create')
            ->with(['id' => 1, 'username' => 'foobar'])
            ->willReturn($user)
        ;

        $virtualFieldsHandler = $this->createStub(VirtualFieldsHandler::class);
        $virtualFieldsHandler
            ->method('expandFields')
            ->willReturnArgument(0)
        ;

        $provider = $this->getProvider(null, $connection, $userFactory, $virtualFieldsHandler);

        $this->assertSame($user, $provider->loadUserById(1));
    }

    public function testFailsToLoadAUserIfTheUsernameDoesNotExist(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $provider = $this->getProvider(null, $connection);

        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage('Could not find user "foobar"');

        $provider->loadUserByIdentifier('foobar');
    }

    public function testRefreshesTheUser(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user->username = 'foobar';

        $connection = $this->createStub(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn(['id' => 1, 'username' => 'foobar'])
        ;

        $userFactory = $this->createStub(UserFactoryInterface::class);
        $userFactory
            ->method('getTable')
            ->willReturn('tl_user')
        ;

        $userFactory
            ->method('supportsClass')
            ->willReturn(true)
        ;

        $userFactory
            ->method('create')
            ->willReturn($user)
        ;

        $virtualFieldsHandler = $this->createStub(VirtualFieldsHandler::class);
        $virtualFieldsHandler
            ->method('expandFields')
            ->willReturnArgument(0)
        ;

        $provider = $this->getProvider(null, $connection, $userFactory, $virtualFieldsHandler);

        $this->assertSame($user, $provider->refreshUser($user));
    }

    public function testFailsToRefreshUnsupportedUsers(): void
    {
        $user = $this->createStub(UserInterface::class);

        $userFactory = $this->createStub(UserFactoryInterface::class);
        $userFactory
            ->method('supportsClass')
            ->willReturn(false)
        ;

        $provider = $this->getProvider(null, null, $userFactory);

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(\sprintf('Unsupported class "%s".', $user::class));

        $provider->refreshUser($user);
    }

    public function testChecksIfAClassIsSupported(): void
    {
        $userFactory = $this->createMock(UserFactoryInterface::class);
        $userFactory
            ->expects($this->exactly(2))
            ->method('supportsClass')
            ->willReturnMap([
                [BackendUser::class, true],
                [FrontendUser::class, false],
            ])
        ;

        $provider = $this->getProvider(null, null, $userFactory);

        $this->assertTrue($provider->supportsClass(BackendUser::class));
        $this->assertFalse($provider->supportsClass(FrontendUser::class));
    }

    public function testUpgradesPasswords(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user->id = 1;

        $userFactory = $this->createStub(UserFactoryInterface::class);
        $userFactory
            ->method('supportsClass')
            ->willReturn(true)
        ;

        $userFactory
            ->method('getTable')
            ->willReturn('tl_user')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with('tl_user', ['password' => 'newsuperhash'], ['id' => 1])
        ;

        $userProvider = $this->getProvider(null, $connection, $userFactory);
        $userProvider->upgradePassword($user, 'newsuperhash');
    }

    public function testFailsToUpgradePasswordsOfUnsupportedUsers(): void
    {
        $user = $this->createStub(PasswordAuthenticatedUserInterface::class);

        $userFactory = $this->createStub(UserFactoryInterface::class);
        $userFactory
            ->method('supportsClass')
            ->willReturn(false)
        ;

        $provider = $this->getProvider(null, null, $userFactory);

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(\sprintf('Unsupported class "%s".', $user::class));

        /** @phpstan-ignore argument.type */
        $provider->upgradePassword($user, 'newsuperhash');
    }

    /**
     * @param UserFactoryInterface<BackendUser|FrontendUser>|null $userFactory
     */
    private function getProvider(ContaoFramework|null $framework = null, Connection|null $connection = null, UserFactoryInterface|null $userFactory = null, VirtualFieldsHandler|null $virtualFieldsHandler = null): ContaoUserProvider
    {
        $framework ??= $this->createContaoFrameworkStub();
        $connection ??= $this->createStub(Connection::class);
        $userFactory ??= $this->createStub(UserFactoryInterface::class);
        $virtualFieldsHandler ??= $this->createStub(VirtualFieldsHandler::class);

        return new ContaoUserProvider($framework, $connection, $userFactory, $virtualFieldsHandler);
    }
}

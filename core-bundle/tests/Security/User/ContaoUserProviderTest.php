<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Test\Security\User;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ContaoUserProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $userProvider = $this->mockUserProvider();

        $this->assertInstanceOf('Contao\CoreBundle\Security\User\ContaoUserProvider', $userProvider);
    }

    public function testLoadsUsersByUsername(): void
    {
        $user = $this->createMock(BackendUser::class);
        $adapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $userProvider = $this->mockUserProvider($framework);

        $this->assertSame($user, $userProvider->loadUserByUsername('foobar'));
    }

    public function testFailsToLoadAUserIfTheUsernameDoesNotExist(): void
    {
        $user = $this->createMock(UserInterface::class);
        $adapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $userProvider = $this->mockUserProvider($framework);

        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('Could not find user "foobar"');

        $userProvider->loadUserByUsername('foobar');
    }

    public function testRefreshesTheUser(): void
    {
        /** @var UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, ['username' => 'foobar']);
        $adapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $userProvider = $this->mockUserProvider($framework);

        $this->assertSame($user, $userProvider->refreshUser($user));
    }

    public function testValidatesTheSessionLifetime(): void
    {
        /** @var UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, ['username' => 'foobar']);
        $userAdapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $configAdapter = $this->mockAdapter(['get']);

        $configAdapter
            ->expects($this->once())
            ->method('get')
            ->with('sessionTimeout')
            ->willReturn(3600)
        ;

        $adapters = [
            BackendUser::class => $userAdapter,
            Config::class => $configAdapter,
        ];

        $framework = $this->mockContaoFramework($adapters);
        $metadata = $this->createMock(MetadataBag::class);

        $metadata
            ->expects($this->once())
            ->method('getLastUsed')
            ->willReturn(time() - 1800)
        ;

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('getMetadataBag')
            ->willReturn($metadata)
        ;

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->never())
            ->method('info')
        ;

        $userProvider = new ContaoUserProvider($framework, $session, BackendUser::class, $logger);

        $this->assertSame($user, $userProvider->refreshUser($user));
    }

    public function testLogsOutUsersWhoHaveBeenInactiveForTooLong(): void
    {
        /** @var UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, ['username' => 'foobar']);
        $userAdapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $configAdapter = $this->mockAdapter(['get']);

        $configAdapter
            ->expects($this->once())
            ->method('get')
            ->with('sessionTimeout')
            ->willReturn(3600)
        ;

        $adapters = [
            BackendUser::class => $userAdapter,
            Config::class => $configAdapter,
        ];

        $framework = $this->mockContaoFramework($adapters);
        $metadata = $this->createMock(MetadataBag::class);

        $metadata
            ->expects($this->once())
            ->method('getLastUsed')
            ->willReturn(time() - 7200)
        ;

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('getMetadataBag')
            ->willReturn($metadata)
        ;

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with('User "foobar" has been logged out automatically due to inactivity')
        ;

        $userProvider = new ContaoUserProvider($framework, $session, BackendUser::class, $logger);

        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "foobar" has been logged out automatically due to inactivity.');

        $this->assertSame($user, $userProvider->refreshUser($user));
    }

    public function testFailsToRefreshUnsupportedUsers(): void
    {
        $user = $this->createMock(UserInterface::class);
        $userProvider = $this->mockUserProvider();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Unsupported class "%s".', \get_class($user)));

        $userProvider->refreshUser($user);
    }

    public function testChecksIfAClassIsSupported(): void
    {
        $userProvider = $this->mockUserProvider();

        $this->assertTrue($userProvider->supportsClass(BackendUser::class));
        $this->assertFalse($userProvider->supportsClass(FrontendUser::class));
    }

    public function testDoesNotHandleUnknownUserClasses(): void
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Unsupported class "LdapUser".');

        $this->mockUserProvider(null, 'LdapUser');
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the "postAuthenticate" hook has been deprecated %s.
     */
    public function testTriggersThePostAuthenticateHook(): void
    {
        /** @var UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, ['username' => 'foobar']);
        $adapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(__CLASS__)
            ->willReturn($this)
        ;

        $GLOBALS['TL_HOOKS']['postAuthenticate'] = [[__CLASS__, 'onPostAuthenticate']];

        $userProvider = $this->mockUserProvider($framework);

        $this->assertSame($user, $userProvider->refreshUser($user));

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * @param UserInterface $user
     */
    public function onPostAuthenticate(UserInterface $user): void
    {
        $this->assertInstanceOf('Contao\BackendUser', $user);
    }

    /**
     * Mocks a user provider.
     *
     * @param ContaoFrameworkInterface|null $framework
     * @param string                        $userClass
     *
     * @return ContaoUserProvider
     */
    private function mockUserProvider(ContaoFrameworkInterface $framework = null, string $userClass = BackendUser::class): ContaoUserProvider
    {
        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        $session = $this->createMock(SessionInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        return new ContaoUserProvider($framework, $session, $userClass, $logger);
    }
}

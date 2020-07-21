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

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ContaoUserProviderTest extends TestCase
{
    public function testLoadsUsersByUsername(): void
    {
        $user = $this->createMock(BackendUser::class);
        $adapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $provider = $this->getProvider($framework);

        $this->assertSame($user, $provider->loadUserByUsername('foobar'));
    }

    public function testFailsToLoadAUserIfTheUsernameDoesNotExist(): void
    {
        $user = $this->createMock(UserInterface::class);
        $adapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $provider = $this->getProvider($framework);

        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('Could not find user "foobar"');

        $provider->loadUserByUsername('foobar');
    }

    public function testRefreshesTheUser(): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

        $adapter = $this->mockConfiguredAdapter(['loadUserByUsername' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $provider = $this->getProvider($framework);

        $this->assertSame($user, $provider->refreshUser($user));
    }

    public function testValidatesTheSessionLifetime(): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

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
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

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
        $provider = $this->getProvider();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Unsupported class "%s".', \get_class($user)));

        $provider->refreshUser($user);
    }

    public function testChecksIfAClassIsSupported(): void
    {
        $provider = $this->getProvider();

        $this->assertTrue($provider->supportsClass(BackendUser::class));
        $this->assertFalse($provider->supportsClass(FrontendUser::class));
    }

    public function testDoesNotHandleUnknownUserClasses(): void
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Unsupported class "LdapUser".');

        $this->getProvider(null, 'LdapUser');
    }

    public function testUpgradesPasswords(): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';
        $user->password = 'superhash';

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $userProvider = $this->getProvider(null, BackendUser::class);
        $userProvider->upgradePassword($user, 'newsuperhash');

        $this->assertSame('newsuperhash', $user->password);
    }

    public function testFailsToUpgradePasswordsOfUnsupportedUsers(): void
    {
        $user = $this->createMock(UserInterface::class);
        $provider = $this->getProvider();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Unsupported class "%s".', \get_class($user)));

        /* @phpstan-ignore-next-line */
        $provider->upgradePassword($user, 'newsuperhash');
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Since contao/core-bundle 4.5: Using the "postAuthenticate" hook has been deprecated %s.
     */
    public function testTriggersThePostAuthenticateHook(): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

        $systemAdapter = $this->mockAdapter(['importStatic']);
        $systemAdapter
            ->expects($this->once())
            ->method('importStatic')
            ->with(static::class)
            ->willReturn($this)
        ;

        $framework = $this->mockContaoFramework([
            BackendUser::class => $this->mockConfiguredAdapter(['loadUserByUsername' => $user]),
            System::class => $systemAdapter,
        ]);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $GLOBALS['TL_HOOKS']['postAuthenticate'][] = [static::class, 'onPostAuthenticate'];

        $provider = $this->getProvider($framework);

        $this->assertSame($user, $provider->refreshUser($user));

        unset($GLOBALS['TL_HOOKS']);
    }

    public function onPostAuthenticate(): void
    {
        // Dummy method to test the postAuthenticate hook
    }

    private function getProvider(ContaoFramework $framework = null, string $userClass = BackendUser::class): ContaoUserProvider
    {
        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        $session = $this->createMock(SessionInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        return new ContaoUserProvider($framework, $session, $userClass, $logger);
    }
}

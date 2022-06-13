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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ContaoUserProviderTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testLoadsUsersByUsername(): void
    {
        $user = $this->createMock(BackendUser::class);
        $adapter = $this->mockConfiguredAdapter(['loadUserByIdentifier' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $provider = $this->getProvider($framework);

        $this->assertSame($user, $provider->loadUserByIdentifier('foobar'));
    }

    public function testFailsToLoadAUserIfTheUsernameDoesNotExist(): void
    {
        $user = $this->createMock(UserInterface::class);
        $adapter = $this->mockConfiguredAdapter(['loadUserByIdentifier' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $provider = $this->getProvider($framework);

        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('Could not find user "foobar"');

        $provider->loadUserByIdentifier('foobar');
    }

    public function testRefreshesTheUser(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';

        $adapter = $this->mockConfiguredAdapter(['loadUserByIdentifier' => $user]);
        $framework = $this->mockContaoFramework([BackendUser::class => $adapter]);

        $provider = $this->getProvider($framework);

        $this->assertSame($user, $provider->refreshUser($user));
    }

    public function testFailsToRefreshUnsupportedUsers(): void
    {
        $user = $this->createMock(UserInterface::class);
        $provider = $this->getProvider();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Unsupported class "%s".', $user::class));

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
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->username = 'foobar';
        $user->password = 'superhash';

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $userProvider = $this->getProvider();
        $userProvider->upgradePassword($user, 'newsuperhash');

        $this->assertSame('newsuperhash', $user->password);
    }

    public function testFailsToUpgradePasswordsOfUnsupportedUsers(): void
    {
        $user = $this->createMock(UserInterface::class);
        $provider = $this->getProvider();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Unsupported class "%s".', $user::class));

        /** @phpstan-ignore-next-line */
        $provider->upgradePassword($user, 'newsuperhash');
    }

    /**
     * @group legacy
     */
    public function testTriggersThePostAuthenticateHook(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using the "postAuthenticate" hook has been deprecated %s.');

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
            BackendUser::class => $this->mockConfiguredAdapter(['loadUserByIdentifier' => $user]),
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
        $framework ??= $this->mockContaoFramework();

        return new ContaoUserProvider($framework, $userClass);
    }
}

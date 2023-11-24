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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
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

        $this->expectException(UserNotFoundException::class);
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
        $user = $this->createMock(PasswordAuthenticatedUserInterface::class);
        $provider = $this->getProvider();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Unsupported class "%s".', $user::class));

        /** @phpstan-ignore-next-line */
        $provider->upgradePassword($user, 'newsuperhash');
    }

    private function getProvider(ContaoFramework|null $framework = null, string $userClass = BackendUser::class): ContaoUserProvider
    {
        $framework ??= $this->mockContaoFramework();

        return new ContaoUserProvider($framework, $userClass);
    }
}

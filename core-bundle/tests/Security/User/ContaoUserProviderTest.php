<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security\User;

use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\Security\SecurityTestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;

class ContaoUserProviderTest extends SecurityTestCase
{
    /**
     * @group legacy
     *
     * @expectedDeprecation Using the ContaoUserProvider class has been deprecated %s.
     */
    public function testCanBeInstantiated(): void
    {
        $provider = $this->mockProvider('backend');

        $this->assertInstanceOf('Contao\CoreBundle\Security\User\ContaoUserProvider', $provider);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @group legacy
     *
     * @expectedDeprecation Using the ContaoUserProvider class has been deprecated %s.
     */
    public function testProvidesTheBackEndUser(): void
    {
        $provider = $this->mockProvider('backend');

        $this->assertInstanceOf('Contao\BackendUser', $provider->loadUserByUsername('backend'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     * @group legacy
     *
     * @expectedDeprecation Using the ContaoUserProvider class has been deprecated %s.
     */
    public function testProvidesTheFrontEndUser(): void
    {
        $provider = $this->mockProvider('frontend');

        $this->assertInstanceOf('Contao\FrontendUser', $provider->loadUserByUsername('frontend'));
    }

    public function testFailsIfTheScopeIsInvalid(): void
    {
        $provider = $this->mockProvider('invalid');

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('frontend');
    }

    public function testFailsIfTheUsernameIsNotSupported(): void
    {
        $provider = $this->mockProvider('frontend');

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('foo');
    }

    public function testFailsIfTheUserIsRefreshed(): void
    {
        $provider = $this->mockProvider('frontend');

        $this->expectException(UnsupportedUserException::class);

        $provider->refreshUser(new User('foo', 'bar'));
    }

    public function testChecksIfAClassIsSupported(): void
    {
        $provider = $this->mockProvider('frontend');

        $this->assertTrue($provider->supportsClass(FrontendUser::class));
    }

    public function testFailsToLoadTheBackEndUserIfThereIsNoContainer(): void
    {
        $provider = $this->mockProvider();

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('backend');
    }

    public function testFailsToLoadTheFrontEndUserIfThereIsNoContainer(): void
    {
        $provider = $this->mockProvider();

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('frontend');
    }

    /**
     * Mocks a Contao user provider.
     *
     * @param string|null $scope
     *
     * @return ContaoUserProvider
     *
     * @group legacy
     *
     * @expectedDeprecation Using the ContaoUserProvider class has been deprecated %s.
     */
    private function mockProvider(string $scope = null): ContaoUserProvider
    {
        $provider = new ContaoUserProvider($this->mockContaoFramework(), $this->mockScopeMatcher());

        if (null !== $scope) {
            $provider->setContainer($this->mockContainerWithScope($scope));
        }

        return $provider;
    }
}

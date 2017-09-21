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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;

class ContaoUserProviderTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);
    }

    public function testCanBeInstantiated(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());

        $this->assertInstanceOf('Contao\CoreBundle\Security\User\ContaoUserProvider', $provider);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProvidesTheBackEndUser(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));

        $this->assertInstanceOf('Contao\BackendUser', $provider->loadUserByUsername('backend'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProvidesTheFrontEndUser(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->assertInstanceOf('Contao\FrontendUser', $provider->loadUserByUsername('frontend'));
    }

    public function testFailsIfTheScopeIsInvalid(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes('invalid'));

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('frontend');
    }

    public function testFailsIfTheUsernameIsNotSupported(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('foo');
    }

    public function testFailsIfTheUserIsRefreshed(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->expectException(UnsupportedUserException::class);

        $provider->refreshUser(new User('foo', 'bar'));
    }

    public function testChecksIfAClassIsSupported(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->assertTrue($provider->supportsClass(FrontendUser::class));
    }

    public function testFailsToLoadTheBackEndUserIfThereIsNoContainer(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('backend');
    }

    public function testFailsToLoadTheFrontEndUserIfThereIsNoContainer(): void
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('frontend');
    }
}

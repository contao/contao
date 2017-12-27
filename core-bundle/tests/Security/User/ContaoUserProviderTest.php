<?php

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

/**
 * Tests the ContaoUserProvider class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoUserProviderTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());

        $this->assertInstanceOf('Contao\CoreBundle\Security\User\ContaoUserProvider', $provider);
    }

    /**
     * Tests loading the user "backend".
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProvidesTheBackEndUser()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));

        $this->assertInstanceOf('Contao\BackendUser', $provider->loadUserByUsername('backend'));
    }

    /**
     * Tests loading the user "frontend".
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProvidesTheFrontEndUser()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->assertInstanceOf('Contao\FrontendUser', $provider->loadUserByUsername('frontend'));
    }

    /**
     * Tests an invalid container scope.
     */
    public function testFailsIfTheScopeIsInvalid()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes('invalid'));

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('frontend');
    }

    /**
     * Tests an unsupported username.
     */
    public function testFailsIfTheUsernameIsNotSupported()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('foo');
    }

    /**
     * Tests refreshing a user.
     */
    public function testFailsIfTheUserIsRefreshed()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->expectException(UnsupportedUserException::class);

        $provider->refreshUser(new User('foo', 'bar'));
    }

    /**
     * Tests the supportsClass() method.
     */
    public function testChecksIfAClassIsSupported()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->assertTrue($provider->supportsClass(FrontendUser::class));
    }

    /**
     * Tests loading the user "backend" without a container.
     */
    public function testFailsToLoadTheBackEndUserIfThereIsNoContainer()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('backend');
    }

    /**
     * Tests loading the user "frontend" without a container.
     */
    public function testFailsToLoadTheFrontEndUserIfThereIsNoContainer()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());

        $this->expectException(UsernameNotFoundException::class);

        $provider->loadUserByUsername('frontend');
    }
}

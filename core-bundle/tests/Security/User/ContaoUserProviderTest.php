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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Tests\TestCase;
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
     * @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
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
    public function testLoadUserBackend()
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
    public function testLoadUserFrontend()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->assertInstanceOf('Contao\FrontendUser', $provider->loadUserByUsername('frontend'));
    }

    /**
     * Tests an invalid container scope.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadWithInvalidScope()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes('invalid'));

        $provider->loadUserByUsername('frontend');
    }

    /**
     * Tests an unsupported username.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUnsupportedUsername()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $provider->loadUserByUsername('foo');
    }

    /**
     * Tests refreshing a user.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testRefreshUser()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $provider->refreshUser(new User('foo', 'bar'));
    }

    /**
     * Tests the supportsClass() method.
     */
    public function testSupportsClass()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());
        $provider->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));

        $this->assertTrue($provider->supportsClass('Contao\FrontendUser'));
    }

    /**
     * Tests loading the user "backend" without a container.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testBackendUserWithoutContainer()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());

        $provider->loadUserByUsername('backend');
    }

    /**
     * Tests loading the user "frontend" without a container.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testFrontendUserWithoutContainer()
    {
        $provider = new ContaoUserProvider($this->framework, $this->mockScopeMatcher());

        $provider->loadUserByUsername('frontend');
    }
}

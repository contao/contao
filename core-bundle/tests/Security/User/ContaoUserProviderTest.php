<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Authentication;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\Security\Core\User\User;

/**
 * Tests the ContaoUserProvider class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoUserProviderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $provider = new ContaoUserProvider();

        $this->assertInstanceOf('Contao\\CoreBundle\\Security\\User\\ContaoUserProvider', $provider);
    }

    /**
     * Tests loading the user "backend".
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoadUserBackend()
    {
        $provider = new ContaoUserProvider();

        $container = new Container();
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $provider->setContainer($container);

        $this->assertInstanceOf('Contao\\BackendUser', $provider->loadUserByUsername('backend'));
    }

    /**
     * Tests loading the user "frontend".
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLoadUserFrontend()
    {
        $provider = new ContaoUserProvider();

        $container = new Container();
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);
        $provider->setContainer($container);

        $this->assertInstanceOf('Contao\\FrontendUser', $provider->loadUserByUsername('frontend'));
    }

    /**
     * Tests with missing container.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadWithoutContainer()
    {
        $provider = new ContaoUserProvider();
        $provider->loadUserByUsername('frontend');
    }

    /**
     * Tests with invalid container scope.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadWithInvalidScope()
    {
        $provider = new ContaoUserProvider();

        $container = new Container();
        $container->addScope(new Scope('request'));
        $container->enterScope('request');
        $provider->setContainer($container);

        $provider->loadUserByUsername('frontend');
    }

    /**
     * Tests an unsupported username.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUnsupportedUsername()
    {
        $provider = new ContaoUserProvider();
        $provider->loadUserByUsername('foo');
    }

    /**
     * Tests refreshing a user.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testRefreshUser()
    {
        $provider = new ContaoUserProvider();
        $provider->refreshUser(new User('foo', 'bar'));
    }

    /**
     * Tests the supportsClass method.
     */
    public function testSupportsClass()
    {
        $provider = new ContaoUserProvider();

        $this->assertTrue($provider->supportsClass('Contao\\FrontendUser'));
    }
}

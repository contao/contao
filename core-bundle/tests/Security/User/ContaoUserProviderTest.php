<?php

/*
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
use Contao\CoreBundle\ContaoFramework;

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
            ->getMockBuilder('Contao\\CoreBundle\\ContaoFramework')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $provider = new ContaoUserProvider($this->framework);

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
        $container = new Container();
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);

        $provider = new ContaoUserProvider($this->framework);
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
        $container = new Container();
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $provider = new ContaoUserProvider($this->framework);
        $provider->setContainer($container);

        $this->assertInstanceOf('Contao\\FrontendUser', $provider->loadUserByUsername('frontend'));
    }

    /**
     * Tests an invalid container scope.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadWithInvalidScope()
    {
        $container = new Container();
        $container->addScope(new Scope('request'));
        $container->enterScope('request');

        $provider = new ContaoUserProvider($this->framework);
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
        $container = new Container();
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $provider = new ContaoUserProvider($this->framework);
        $provider->setContainer($container);

        $provider->loadUserByUsername('foo');
    }

    /**
     * Tests refreshing a user.
     *
     * @expectedException \Symfony\Component\Security\Core\Exception\UnsupportedUserException
     */
    public function testRefreshUser()
    {
        $container = new Container();
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $provider = new ContaoUserProvider($this->framework);
        $provider->setContainer($container);

        $provider->refreshUser(new User('foo', 'bar'));
    }

    /**
     * Tests the supportsClass() method.
     */
    public function testSupportsClass()
    {
        $container = new Container();
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $provider = new ContaoUserProvider($this->framework);
        $provider->setContainer($container);

        $this->assertTrue($provider->supportsClass('Contao\\FrontendUser'));
    }
}

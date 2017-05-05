<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Menu;

use Contao\CoreBundle\Menu\AbstractMenuProvider;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Tests the AbstractMenuProvider class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class AbstractMenuProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the getUser() method without token storage.
     */
    public function testGetUserWithoutTokenStorage()
    {
        $router = $this->getMock(RouterInterface::class);
        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        /** @var AbstractMenuProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this
            ->getMockBuilder(AbstractMenuProvider::class)
            ->setConstructorArgs([$router, $requestStack])
            ->getMockForAbstractClass()
        ;

        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('getUser');
        $method->setAccessible(true);

        $this->setExpectedException('RuntimeException', 'No token storage provided');

        $method->invoke($provider);
    }

    /**
     * Tests the getUser() method without token.
     */
    public function testGetUserWithoutToken()
    {
        $router = $this->getMock(RouterInterface::class);
        $tokenStorage = $this->getMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn(null)
        ;

        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        /** @var AbstractMenuProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this
            ->getMockBuilder(AbstractMenuProvider::class)
            ->setConstructorArgs([$router, $requestStack, $tokenStorage])
            ->getMockForAbstractClass()
        ;

        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('getUser');
        $method->setAccessible(true);

        $this->setExpectedException('RuntimeException', 'No token provided');

        $method->invoke($provider);
    }

    /**
     * Tests the getUser() method without user.
     */
    public function testGetUserWithoutUser()
    {
        $router = $this->getMock(RouterInterface::class);
        $token = $this->getMock(TokenInterface::class);

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn(null)
        ;

        $tokenStorage = $this->getMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        /** @var AbstractMenuProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this
            ->getMockBuilder(AbstractMenuProvider::class)
            ->setConstructorArgs([$router, $requestStack, $tokenStorage])
            ->getMockForAbstractClass()
        ;

        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('getUser');
        $method->setAccessible(true);

        $this->setExpectedException('RuntimeException', 'The token does not contain a user');

        $method->invoke($provider);
    }

    /**
     * Tests the addMenuItem() method without request.
     */
    public function testAddMenuItemWithoutRequest()
    {
        $router = $this->getMock(RouterInterface::class);
        $tokenStorage = $this->getMock(TokenStorageInterface::class);
        $requestStack = new RequestStack();

        /** @var AbstractMenuProvider|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this
            ->getMockBuilder(AbstractMenuProvider::class)
            ->setConstructorArgs([$router, $requestStack, $tokenStorage])
            ->getMockForAbstractClass()
        ;

        $factory = new MenuFactory();
        $menu = $factory->createItem('foo');

        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('addMenuItem');
        $method->setAccessible(true);
        $method->invokeArgs($provider, [$menu, $factory, 'page', 'Pages', 'pagemounts']);

        $this->assertFalse($menu->hasChildren());
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\HttpKernel;

use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\HttpKernel\ControllerResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolverTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $resolver = new ControllerResolver(
            $this->createMock(ControllerResolverInterface::class),
            new FragmentRegistry()
        );

        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\ControllerResolver', $resolver);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface', $resolver);
    }

    public function testSetsTheControllerAttributeFromTheFragmentRegistry(): void
    {
        $config = new FragmentConfig('Foo\Bar\FooBarController');
        $registry = $this->createMock(FragmentRegistry::class);

        $registry
            ->expects($this->once())
            ->method('get')
            ->with('foo.bar')
            ->willReturn($config)
        ;

        $request = new Request();
        $request->attributes->set('_controller', 'foo.bar');

        $resolver = new ControllerResolver($this->createMock(ControllerResolverInterface::class), $registry);
        $resolver->getController($request);

        $this->assertSame('Foo\Bar\FooBarController', $request->attributes->get('_controller'));
    }

    public function testForwardsTheControllerToTheDecoratedClass(): void
    {
        $decorated = $this->createMock(ControllerResolverInterface::class);

        $decorated
            ->expects($this->once())
            ->method('getController')
        ;

        $resolver = new ControllerResolver($decorated, new FragmentRegistry());
        $resolver->getController(new Request());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The Symfony\Component\HttpKernel\Controller\ControllerResolverInterface::getArguments method is deprecated %s.
     */
    public function testForwardsArgumentsToDecoratedClass(): void
    {
        $decorated = $this->createMock(ControllerResolverInterface::class);

        $decorated
            ->expects($this->once())
            ->method('getArguments')
        ;

        $resolver = new ControllerResolver($decorated, new FragmentRegistry());
        $resolver->getArguments(new Request(), '');
    }
}

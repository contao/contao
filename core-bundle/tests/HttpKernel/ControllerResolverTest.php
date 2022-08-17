<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\HttpKernel;

use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\HttpKernel\ControllerResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolverTest extends TestCase
{
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

        $decorated = $this->createMock(ControllerResolverInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getController')
            ->willReturn(false)
        ;

        $resolver = new ControllerResolver($decorated, $registry);
        $resolver->getController($request);

        $this->assertSame('Foo\Bar\FooBarController', $request->attributes->get('_controller'));
    }

    public function testForwardsTheControllerToTheDecoratedClass(): void
    {
        $decorated = $this->createMock(ControllerResolverInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getController')
            ->willReturn(false)
        ;

        $resolver = new ControllerResolver($decorated, new FragmentRegistry());
        $resolver->getController(new Request());
    }

    public function testIgnoresControllersThatAreNotString(): void
    {
        $registry = $this->createMock(FragmentRegistry::class);
        $registry
            ->expects($this->never())
            ->method('get')
        ;

        $request = new Request();
        $request->attributes->set('_controller', new ControllerReference('foo'));

        $decorated = $this->createMock(ControllerResolverInterface::class);
        $decorated
            ->method('getController')
            ->willReturn(false)
        ;

        $resolver = new ControllerResolver($decorated, $registry);
        $resolver->getController($request);
    }
}

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
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Kernel;

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

    public function testForwardsArgumentsToDecoratedClass(): void
    {
        if (Kernel::MAJOR_VERSION > 3) {
            $this->markTestSkipped('The getArguments() method has been removed in Symfony 4');

            return;
        }

        $decorated = $this->createMock(ControllerResolverInterface::class);
        $decorated
            ->expects($this->once())
            ->method('getArguments')
            ->willReturn([])
        ;

        $resolver = new ControllerResolver($decorated, new FragmentRegistry());
        $resolver->getArguments(new Request(), '');
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Session;

class AddSessionBagsPassTest extends TestCase
{
    public function testAddsTheSessionBags(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('session.foobar', new Definition(Session::class));
        $container->setAlias('session', 'session.foobar');
        $container->setDefinition('contao.session.contao_backend', new Definition(ArrayAttributeBag::class));
        $container->setDefinition('contao.session.contao_frontend', new Definition(ArrayAttributeBag::class));

        $pass = new AddSessionBagsPass();
        $pass->process($container);

        $methodCalls = $container->findDefinition('session')->getMethodCalls();

        $this->assertCount(2, $methodCalls);
        $this->assertSame('registerBag', $methodCalls[0][0]);
        $this->assertSame('registerBag', $methodCalls[1][0]);
        $this->assertInstanceOf(Reference::class, $methodCalls[0][1][0]);
        $this->assertInstanceOf(Reference::class, $methodCalls[1][1][0]);
        $this->assertSame('contao.session.contao_backend', (string) $methodCalls[0][1][0]);
        $this->assertSame('contao.session.contao_frontend', (string) $methodCalls[1][1][0]);
    }

    public function testDoesNotAddsTheSessionBagsIfThereIsNoSession(): void
    {
        $container = new ContainerBuilder();

        $pass = new AddSessionBagsPass();
        $pass->process($container);

        $this->assertFalse($container->has('session'));
    }
}

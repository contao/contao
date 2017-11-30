<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterFragmentsPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new RegisterFragmentsPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass', $pass);
    }

    public function testRegistersTheFragments(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element');

        $moduleController = new Definition('App\Fragments\LoginController');
        $moduleController->addTag('contao.frontend_module');

        $container = $this->mockContainer();
        $container->setDefinition('app.fragments.content_controller', $contentController);
        $container->setDefinition('app.fragments.module_controller', $moduleController);

        $pass = new RegisterFragmentsPass();
        $pass->process($container);

        $methodCalls = $container->getDefinition('contao.fragment.registry')->getMethodCalls();

        $this->assertSame('add', $methodCalls[0][0]);
        $this->assertSame('contao.content_element.text', $methodCalls[0][1][0]);
        $this->assertStringMatchesFormat('contao.fragment._config_%S', (string) $methodCalls[0][1][1]);

        $arguments = $container->getDefinition((string) $methodCalls[0][1][1])->getArguments();

        $this->assertSame('app.fragments.content_controller', $arguments[0]);
        $this->assertSame('inline', $arguments[1]);

        $this->assertSame('add', $methodCalls[1][0]);
        $this->assertSame('contao.frontend_module.login', $methodCalls[1][1][0]);
        $this->assertStringMatchesFormat('contao.fragment._config_%S', (string) $methodCalls[1][1][1]);

        $arguments = $container->getDefinition((string) $methodCalls[1][1][1])->getArguments();

        $this->assertSame('app.fragments.module_controller', $arguments[0]);
        $this->assertSame('inline', $arguments[1]);
    }

    public function testUsesTheGivenAttributes(): void
    {
        $attributes = [
            'type' => 'foo',
            'renderer' => 'esi',
            'method' => 'bar',
        ];

        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element', $attributes);

        $container = $this->mockContainer();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        $pass = new RegisterFragmentsPass();
        $pass->process($container);

        $methodCalls = $container->getDefinition('contao.fragment.registry')->getMethodCalls();

        $this->assertSame('add', $methodCalls[0][0]);
        $this->assertSame('contao.content_element.foo', $methodCalls[0][1][0]);
        $this->assertStringMatchesFormat('contao.fragment._config_%S', (string) $methodCalls[0][1][1]);

        $arguments = $container->getDefinition((string) $methodCalls[0][1][1])->getArguments();

        $this->assertSame('app.fragments.content_controller:bar', $arguments[0]);
        $this->assertSame('esi', $arguments[1]);
    }

    public function testRegistersThePreHandlers(): void
    {
        $contentController = new Definition(FragmentPreHandlerInterface::class);
        $contentController->addTag('contao.content_element');

        $container = $this->mockContainer();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        $pass = new RegisterFragmentsPass();
        $pass->process($container);

        $arguments = $container->getDefinition('contao.fragment.pre_handlers')->getArguments();

        $this->assertArrayHasKey('contao.content_element.fragment_pre_handler_interface', $arguments[0]);

        $this->assertSame(
            'app.fragments.content_controller',
            (string) $arguments[0]['contao.content_element.fragment_pre_handler_interface']
        );
    }

    public function testFailsIfThereIsNoPreHandlersDefinition(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.fragment.registry', new Definition());
        $container->setDefinition('app.fragments.content_controller', $contentController);

        $pass = new RegisterFragmentsPass();

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Missing service definition for "contao.fragment.pre_handlers"');

        $pass->process($container);
    }

    public function testDoesNothingIfThereIsNoFragmentRegistry(): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        $container
            ->expects($this->never())
            ->method('findDefinition')
        ;

        $pass = new RegisterFragmentsPass();
        $pass->process($container);
    }
}

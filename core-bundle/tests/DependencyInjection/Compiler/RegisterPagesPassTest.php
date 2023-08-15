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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Controller\FrontendModule\TwoFactorController;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterPagesPass;
use Contao\CoreBundle\Fixtures\Controller\Page\TestPageController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendIndex;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RegisterPagesPassTest extends TestCase
{
    public function testDoesNothingIfContainerDoesNotHavePageRegistry(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('contao.routing.page_registry')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('findDefinition')
        ;

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }

    public function testGetsPageTypeFromAttributes(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(static fn ($arguments) => 'my_type' === $arguments[0])
            )
        ;

        $definition = new Definition(AbstractController::class);
        $definition->addTag('contao.page', ['type' => 'my_type']);

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }

    public function testGetsPageTypeFromClass(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(static fn ($arguments) => 'contao_core_bundle' === $arguments[0])
            )
        ;

        $definition = new Definition(ContaoCoreBundle::class);
        $definition->addTag('contao.page');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }

    public function testStripsControllerSuffixOnGetPageTypeFromClass(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(static fn ($arguments) => 'two_factor' === $arguments[0])
            )
        ;

        $definition = new Definition(TwoFactorController::class);
        $definition->addTag('contao.page');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }

    public function testStripsPageControllerSuffixOnGetPageTypeFromClass(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(static fn ($arguments) => 'test' === $arguments[0])
            )
        ;

        $definition = new Definition(TestPageController::class);
        $definition->addTag('contao.page');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }

    public function testSetsControllerFromArguments(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(
                    function ($arguments) {
                        $definition = $arguments[1];
                        $this->assertInstanceOf(Definition::class, $definition);

                        return 'MyController::action' === $definition->getArgument(5)['_controller'];
                    }
                )
            )
        ;

        $definition = new Definition(TestPageController::class);
        $definition->addTag('contao.page', ['defaults' => ['_controller' => 'MyController::action']]);

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }

    public function testSetsControllerMethodAndMakesServicePublic(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(
                    function ($arguments) {
                        $definition = $arguments[1];
                        $this->assertInstanceOf(Definition::class, $definition);

                        return 'test.controller:action' === $definition->getArgument(5)['_controller'];
                    }
                )
            )
        ;

        $definition = new Definition(TestPageController::class);
        $definition->addTag('contao.page', ['method' => 'action']);
        $definition->setPublic(false);

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);

        $this->assertTrue($definition->isPublic());
    }

    public function testSetsControllerInvokeMethodAndMakesServicePublic(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(
                    function ($arguments) {
                        $definition = $arguments[1];
                        $this->assertInstanceOf(Definition::class, $definition);

                        return 'test.controller' === $definition->getArgument(5)['_controller'];
                    }
                )
            )
        ;

        $definition = new Definition(TestPageController::class);
        $definition->addTag('contao.page');
        $definition->setPublic(false);

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);

        $this->assertTrue($definition->isPublic());
    }

    public function testAddsContainerCallIfClassExtendsSymfonyAbstractController(): void
    {
        $definition = $this
            ->getMockBuilder(Definition::class)
            ->setConstructorArgs([TestPageController::class])
            ->onlyMethods(['addMethodCall'])
            ->getMock()
        ;

        $definition
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                $this->callback(static fn ($method) => 'setContainer' === $method),
                $this->callback(
                    static fn (array $arguments) => 1 === \count($arguments)
                        && $arguments[0] instanceof Reference
                        && ContainerInterface::class === (string) $arguments[0]
                )
            )
        ;

        $definition->addTag('contao.page');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $this->createMock(Definition::class));
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }

    public function testUsesFrontendIndexControllerIfClassIsNotCallable(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(
                    function ($arguments) {
                        $definition = $arguments[1];
                        $this->assertInstanceOf(Definition::class, $definition);

                        return FrontendIndex::class.'::renderPage' === $definition->getArgument(5)['_controller'];
                    }
                )
            )
        ;

        $definition = new Definition(ContaoCoreBundle::class);
        $definition->addTag('contao.page');
        $definition->setPublic(false);

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);

        $this->assertFalse($definition->isPublic());
    }

    public function testRegistersDynamicPageInterface(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(
                    function ($arguments) {
                        $reference = $arguments[2];
                        $this->assertInstanceOf(Reference::class, $reference);

                        return 'test.controller' === (string) $reference;
                    }
                )
            )
        ;

        $definition = new Definition(TestPageController::class);
        $definition->addTag('contao.page');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }

    public function testRegistersContentCompositionInterface(): void
    {
        $registry = $this->createMock(Definition::class);
        $registry
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'add',
                $this->callback(
                    function ($arguments) {
                        $reference = $arguments[3];
                        $this->assertInstanceOf(Reference::class, $reference);

                        return 'test.controller' === (string) $reference;
                    }
                )
            )
        ;

        $definition = new Definition(TestPageController::class);
        $definition->addTag('contao.page');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.routing.page_registry', $registry);
        $container->setDefinition('test.controller', $definition);

        $pass = new RegisterPagesPass();
        $pass->process($container);
    }
}

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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\HttpKernel\ModelArgumentResolver;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ModelArgumentResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['objPage']);

        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider getArguments
     */
    public function testResolvesTheModel(string $name, string $class): void
    {
        unset($GLOBALS['objPage']);

        System::setContainer($this->getContainerWithContaoConfiguration());

        $pageModel = $this->createMock(PageModel::class);
        $adapter = $this->mockConfiguredAdapter(['findByPk' => $pageModel]);
        $framework = $this->mockContaoFramework([$class => $adapter]);

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $metadata = new ArgumentMetadata($name, $class, false, false, '');

        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());
        $generator = $resolver->resolve($request, $metadata);

        foreach ($generator as $resolved) {
            $this->assertSame($pageModel, $resolved);
        }
    }

    public function getArguments(): \Generator
    {
        yield ['pageModel', PageModel::class];
        yield ['foobar', PageModel::class];
    }

    public function testResolvesAttributeInstances(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $pageModel = $this->createMock(PageModel::class);
        $framework = $this->mockContaoFramework();

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', $pageModel);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $metadata = new ArgumentMetadata('pageModel', PageModel::class, false, false, '');

        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());
        $generator = $resolver->resolve($request, $metadata);

        foreach ($generator as $resolved) {
            $this->assertSame($pageModel, $resolved);
        }
    }

    public function testDoesNothingIfOutsideTheContaoScope(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $request = Request::create('/foobar');
        $metadata = new ArgumentMetadata('foobar', 'string', false, false, '');
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertSame([], $resolver->resolve($request, $metadata));
    }

    public function testDoesNothingIfTheArgumentTypeDoesNotMatch(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $request = Request::create('/foobar');
        $request->attributes->set('foobar', 'test');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $metadata = new ArgumentMetadata('foobar', 'string', false, false, '');
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertSame([], $resolver->resolve($request, $metadata));
    }

    public function testDoesNothingIfTheArgumentNameIsNotFound(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $request = Request::create('/foobar');
        $request->attributes->set('notAPage', 42);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $metadata = new ArgumentMetadata('foobar', PageModel::class, false, false, '');
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertSame([], $resolver->resolve($request, $metadata));
    }

    public function testSupportsNullableArguments(): void
    {
        $pageModel = $this->createMock(PageModel::class);
        $adapter = $this->mockConfiguredAdapter(['findByPk' => $pageModel]);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $metadata = new ArgumentMetadata('pageModel', PageModel::class, false, false, '', true);
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertSame([$pageModel], $resolver->resolve($request, $metadata));
    }

    public function testChecksIfTheModelExistsIfTheArgumentIsNotNullable(): void
    {
        $adapter = $this->mockConfiguredAdapter(['findByPk' => null]);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $metadata = new ArgumentMetadata('pageModel', PageModel::class, false, false, '');
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertSame([], $resolver->resolve($request, $metadata));
    }

    public function testReturnsTheGlobalPageModel(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $pageModel = $this->mockClassWithProperties(PageModel::class, ['id' => 42]);
        $GLOBALS['objPage'] = $pageModel;

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $metadata = new ArgumentMetadata('pageModel', PageModel::class, false, false, '', true);
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        foreach ($resolver->resolve($request, $metadata) as $model) {
            $this->assertSame($pageModel, $model);
        }
    }
}

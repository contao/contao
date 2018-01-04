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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\HttpKernel\ModelArgumentResolver;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ModelArgumentResolverTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $resolver = new ModelArgumentResolver($this->mockContaoFramework(), $this->mockScopeMatcher());

        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\ModelArgumentResolver', $resolver);
    }

    /**
     * @param string $name
     * @param string $class
     *
     * @dataProvider getArguments
     */
    public function testResolvesTheModel(string $name, string $class): void
    {
        $pageModel = new PageModel();
        $pageModel->setRow(['id' => 42]);

        $adapter = $this->mockConfiguredAdapter(['findByPk' => $pageModel]);
        $framework = $this->mockContaoFramework([$class => $adapter]);

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $metadata = new ArgumentMetadata($name, $class, false, false, '');

        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());
        $generator = $resolver->resolve($request, $metadata);

        $this->assertInstanceOf('Generator', $generator);

        foreach ($generator as $resolved) {
            $this->assertSame($pageModel, $resolved);
        }
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return [
            ['pageModel', PageModel::class],
            ['foobar', PageModel::class],
            ['foobar', 'PageModel'],
        ];
    }

    public function testDoesNothingIfOutsideTheContaoScope(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $request = Request::create('/foobar');
        $argument = new ArgumentMetadata('foobar', 'string', false, false, '');
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertFalse($resolver->supports($request, $argument));
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

        $argument = new ArgumentMetadata('foobar', 'string', false, false, '');
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertFalse($resolver->supports($request, $argument));
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

        $argument = new ArgumentMetadata('foobar', PageModel::class, false, false, '');
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertFalse($resolver->supports($request, $argument));
    }

    public function testSupportsNullableArguments(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $argument = new ArgumentMetadata('pageModel', PageModel::class, false, false, '', true);
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertTrue($resolver->supports($request, $argument));
    }

    public function testChecksIfTheModelExistsIfTheArgumentIsNotNullable(): void
    {
        $adapter = $this->mockConfiguredAdapter(['findByPk' => null]);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $argument = new ArgumentMetadata('pageModel', PageModel::class, false, false, '');
        $resolver = new ModelArgumentResolver($framework, $this->mockScopeMatcher());

        $this->assertFalse($resolver->supports($request, $argument));
    }
}

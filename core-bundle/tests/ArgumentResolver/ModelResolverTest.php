<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\ArgumentResolver;

use Contao\CoreBundle\ArgumentResolver\ModelResolver;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ModelResolverTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $resolver = new ModelResolver($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\ArgumentResolver\ModelResolver', $resolver);
    }

    public function testResolvesTheModel(): void
    {
        $pageModel = new PageModel();
        $pageModel->setRow(['id' => 42]);

        $adapter = $this->mockConfiguredAdapter(['findByPk' => $pageModel]);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);

        $metadata = new ArgumentMetadata('pageModel', PageModel::class, false, false, '');

        $resolver = new ModelResolver($framework);
        $generator = $resolver->resolve($request, $metadata);

        $this->assertInstanceOf('Generator', $generator);

        foreach ($generator as $resolved) {
            $this->assertSame($pageModel, $resolved);
        }
    }

    public function testChecksIfTheRequestAttributeExists(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $request = Request::create('/foobar');
        $argument = new ArgumentMetadata('foobar', 'string', false, false, '');

        $resolver = new ModelResolver($framework);
        $resolver->supports($request, $argument);
    }

    public function testChecksIfTheArgumentTypeIsCorrect(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $request = Request::create('/foobar');
        $request->attributes->set('foobar', 'test');

        $argument = new ArgumentMetadata('foobar', 'string', false, false, '');
        $resolver = new ModelResolver($framework);

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

        $argument = new ArgumentMetadata('pageModel', PageModel::class, false, false, '', true);
        $resolver = new ModelResolver($framework);

        $this->assertTrue($resolver->supports($request, $argument));
    }

    public function testChecksIfTheModelExistsIfTheArgumentIsNotNullable(): void
    {
        $adapter = $this->mockConfiguredAdapter(['findByPk' => null]);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $request = Request::create('/foobar');
        $request->attributes->set('pageModel', 42);

        $argument = new ArgumentMetadata('pageModel', PageModel::class, false, false, '');
        $resolver = new ModelResolver($framework);

        $this->assertFalse($resolver->supports($request, $argument));
    }
}

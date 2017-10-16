<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment\PageType;

use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\CoreBundle\Fragment\PageType\DefaultPageTypeRenderer;
use Contao\CoreBundle\Fragment\SimpleRenderingInformationProviderInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class DefaultPageTypeRendererTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            'Contao\CoreBundle\Fragment\PageType\DefaultPageTypeRenderer',
            $this->mockRenderer()
        );
    }

    public function testSupportsPageModels(): void
    {
        $this->assertTrue($this->mockRenderer()->supports(new PageModel()));
    }

    public function testRendersPageModels(): void
    {
        $page = new PageModel();
        $page->setRow(['id' => 42]);

        $GLOBALS['objPage'] = $page;

        $expectedControllerReference = new ControllerReference(
            'test',
            [
                'pageModel' => 42,
            ]
        );

        $registry = new FragmentRegistry();

        $registry->addFragment(
            FragmentRegistryInterface::PAGE_TYPE_FRAGMENT.'.identifier',
            new \stdClass(),
            [
                'tag' => FragmentRegistryInterface::PAGE_TYPE_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
            ]
        );

        $handler = $this->createMock(FragmentHandler::class);

        $handler
            ->expects($this->once())
            ->method('render')
            ->with($this->equalTo($expectedControllerReference))
        ;

        $model = new PageModel();
        $model->setRow(['id' => 42, 'type' => 'identifier']);

        $renderer = $this->mockRenderer($registry, $handler);
        $renderer->render($model);
    }

    public function testRendersPageModelsWithRenderingInformation(): void
    {
        $page = new PageModel();
        $page->setRow(['id' => 42]);

        $GLOBALS['objPage'] = $page;

        $expectedControllerReference = new ControllerReference(
            'test',
            [
                'pageModel' => 42,
                'foo' => 'bar',
            ],
            [
                'bar' => 'foo',
            ]
        );

        $fragment = $this->createMock(SimpleRenderingInformationProviderInterface::class);

        $fragment
            ->expects($this->once())
            ->method('getControllerRequestAttributes')
            ->willReturn(['pageModel' => 42, 'foo' => 'bar'])
        ;

        $fragment
            ->expects($this->once())
            ->method('getControllerQueryParameters')
            ->willReturn(['bar' => 'foo'])
        ;

        $registry = new FragmentRegistry();

        $registry->addFragment(
            FragmentRegistryInterface::PAGE_TYPE_FRAGMENT.'.identifier',
            $fragment,
            [
                'tag' => FragmentRegistryInterface::PAGE_TYPE_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
            ]
        );

        $handler = $this->createMock(FragmentHandler::class);

        $handler
            ->expects($this->once())
            ->method('render')
            ->with($this->equalTo($expectedControllerReference))
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $model = new PageModel();
        $model->setRow(['id' => 42, 'type' => 'identifier']);

        $renderer = $this->mockRenderer($registry, $handler, $requestStack);
        $renderer->render($model);
    }

    public function testIgnoresTheRenderingInformationIfThereIsNoRequest(): void
    {
        $page = new PageModel();
        $page->setRow(['id' => 42]);

        $GLOBALS['objPage'] = $page;

        $expectedControllerReference = new ControllerReference(
            'test',
            [
                'pageModel' => 42,
            ]
        );

        $fragment = $this->createMock(SimpleRenderingInformationProviderInterface::class);

        $fragment
            ->expects($this->never())
            ->method('getControllerRequestAttributes')
        ;

        $fragment
            ->expects($this->never())
            ->method('getControllerQueryParameters')
        ;

        $registry = new FragmentRegistry();

        $registry->addFragment(
            FragmentRegistryInterface::PAGE_TYPE_FRAGMENT.'.identifier',
            $fragment,
            [
                'tag' => FragmentRegistryInterface::PAGE_TYPE_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
            ]
        );

        $handler = $this->createMock(FragmentHandler::class);

        $handler
            ->expects($this->once())
            ->method('render')
            ->with($this->equalTo($expectedControllerReference))
        ;

        $model = new PageModel();
        $model->setRow(['id' => 42, 'type' => 'identifier']);

        $renderer = $this->mockRenderer($registry, $handler);
        $renderer->render($model);
    }

    /**
     * Mocks a default front end module renderer.
     *
     * @param FragmentRegistryInterface|null $registry
     * @param FragmentHandler|null           $handler
     * @param RequestStack|null              $requestStack
     *
     * @return DefaultPageTypeRenderer
     */
    private function mockRenderer(FragmentRegistryInterface $registry = null, FragmentHandler $handler = null, RequestStack $requestStack = null): DefaultPageTypeRenderer
    {
        if (null === $registry) {
            $registry = new FragmentRegistry();
        }

        if (null === $handler) {
            $handler = $this->createMock(FragmentHandler::class);
        }

        if (null === $requestStack) {
            $requestStack = new RequestStack();
        }

        return new DefaultPageTypeRenderer($registry, $handler, $requestStack);
    }
}

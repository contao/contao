<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentHandler;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\Reference\FragmentReference;
use Contao\CoreBundle\Fragment\UnknownFragmentException;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler as BaseFragmentHandler;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class FragmentHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $fragmentHandler = $this->mockFragmentHandler();

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\FragmentHandler', $fragmentHandler);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Fragment\FragmentHandler', $fragmentHandler);
    }

    public function testThrowsAnExceptionIfTheFragmentNameIsInvalid(): void
    {
        $fragmentHandler = $this->mockFragmentHandler();

        $this->expectException(UnknownFragmentException::class);

        $fragmentHandler->render(new FragmentReference('foo.bar'));
    }

    /**
     * @param string $renderingStrategy
     *
     * @dataProvider getRenderingStrategies
     */
    public function testPassesTheRenderingStrategyToTheRenderer(string $renderingStrategy): void
    {
        $uri = new FragmentReference('foo.bar');

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', new FragmentConfig('foo.bar', $renderingStrategy));

        $request = new Request();

        $renderers = $this->mockServiceLocatorWithRenderer(
            $renderingStrategy,
            [$uri, $request, ['ignore_errors' => false]]
        );

        $fragmentHandler = $this->mockFragmentHandler($fragmentRegistry, $renderers, null, $request);
        $fragmentHandler->render($uri);
    }

    /**
     * @return array
     */
    public function getRenderingStrategies(): array
    {
        return [['inline'], ['esi']];
    }

    /**
     * @param array $options
     *
     * @dataProvider getOptions
     */
    public function testPassesTheOptionsToTheRenderer(array $options): void
    {
        $uri = new FragmentReference('foo.bar');

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', new FragmentConfig('foo.bar', 'inline', $options));

        $request = new Request();

        $renderers = $this->mockServiceLocatorWithRenderer(
            'inline',
            [$uri, $request, $options + ['ignore_errors' => false]]
        );

        $fragmentHandler = $this->mockFragmentHandler($fragmentRegistry, $renderers, null, $request);
        $fragmentHandler->render($uri);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return [
            [['foo' => 'bar']],
            [['bar' => 'baz']],
        ];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddsThePageIdFromTheGlobalPageObject(): void
    {
        $uri = new FragmentReference('foo.bar');

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', new FragmentConfig('foo.bar', 'inline', ['foo' => 'bar']));

        $callback = $this->callback(
            function () use ($uri) {
                return isset($uri->attributes['pageModel']) && 42 === $uri->attributes['pageModel'];
            }
        );

        $renderers = $this->mockServiceLocatorWithRenderer('inline', [$callback]);

        $GLOBALS['objPage'] = new PageModel();
        $GLOBALS['objPage']->id = 42;

        $fragmentHandler = $this->mockFragmentHandler($fragmentRegistry, $renderers);
        $fragmentHandler->render($uri);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotOverrideAGivenPageId(): void
    {
        $uri = new FragmentReference('foo.bar', ['pageModel' => 99]);

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', new FragmentConfig('foo.bar', 'inline', ['foo' => 'bar']));

        $callback = $this->callback(
            function () use ($uri) {
                return isset($uri->attributes['pageModel']) && 99 === $uri->attributes['pageModel'];
            }
        );

        $renderers = $this->mockServiceLocatorWithRenderer('inline', [$callback]);

        $GLOBALS['objPage'] = new PageModel();
        $GLOBALS['objPage']->id = 42;

        $fragmentHandler = $this->mockFragmentHandler($fragmentRegistry, $renderers);
        $fragmentHandler->render($uri);
    }

    public function testExecutesThePreHandlers(): void
    {
        $uri = new FragmentReference('foo.bar');
        $config = new FragmentConfig('foo.bar', 'inline', ['foo' => 'bar']);

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', $config);

        $preHandlers = $this->createMock(FragmentPreHandlerInterface::class);

        $preHandlers
            ->expects($this->once())
            ->method('preHandleFragment')
            ->with($uri, $config)
        ;

        $preHandlers = $this->mockServiceLocator('foo.bar', $preHandlers);
        $renderers = $this->mockServiceLocatorWithRenderer('inline');

        $fragmentHandler = $this->mockFragmentHandler($fragmentRegistry, $renderers, $preHandlers);
        $fragmentHandler->render($uri);
    }

    public function testPassesUnknownUrisToTheBaseFragmentHandler(): void
    {
        $baseHandler = $this->createMock(BaseFragmentHandler::class);

        $baseHandler
            ->expects($this->once())
            ->method('render')
        ;

        $fragmentRegistry = $this->createMock(FragmentRegistry::class);

        $fragmentRegistry
            ->expects($this->never())
            ->method('get')
        ;

        $fragmentHandler = $this->mockFragmentHandler($fragmentRegistry, null, null, null, $baseHandler);
        $fragmentHandler->render('foo.bar');
    }

    public function testConvertsExceptionsToResponseExceptions(): void
    {
        $uri = new FragmentReference('foo.bar');

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', new FragmentConfig('foo.bar', 'foobar'));

        $request = new Request();

        $response = new Response();
        $response->setStatusCode(404);

        $renderers = $this->mockServiceLocatorWithRenderer('foobar', [$uri, $request], $response);
        $fragmentHandler = $this->mockFragmentHandler($fragmentRegistry, $renderers, null, $request);

        $this->expectException(ResponseException::class);

        $fragmentHandler->render($uri);
    }

    /**
     * Mocks a fragment handler.
     *
     * @param FragmentRegistry|null    $registry
     * @param ServiceLocator|null      $renderers
     * @param ServiceLocator|null      $preHandlers
     * @param Request|null             $request
     * @param BaseFragmentHandler|null $fragmentHandler
     *
     * @return FragmentHandler
     */
    private function mockFragmentHandler(FragmentRegistry $registry = null, ServiceLocator $renderers = null, ServiceLocator $preHandlers = null, Request $request = null, BaseFragmentHandler $fragmentHandler = null): FragmentHandler
    {
        if (null === $registry) {
            $registry = new FragmentRegistry();
        }

        if (null === $renderers) {
            $renderers = new ServiceLocator([]);
        }

        if (null === $preHandlers) {
            $preHandlers = new ServiceLocator([]);
        }

        if (null === $request) {
            $request = new Request();
        }

        if (null === $fragmentHandler) {
            $fragmentHandler = $this->createMock(BaseFragmentHandler::class);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new FragmentHandler($renderers, $fragmentHandler, $requestStack, $registry, $preHandlers, true);
    }

    /**
     * Mocks a service container with a fragment renderer.
     *
     * @param string        $name
     * @param array|null    $with
     * @param Response|null $response
     *
     * @return ServiceLocator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockServiceLocatorWithRenderer(string $name, array $with = null, Response $response = null): ServiceLocator
    {
        $renderer = $this->createMock(FragmentRendererInterface::class);

        $renderer
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name)
        ;

        $method = $renderer
            ->expects($this->once())
            ->method('render')
        ;

        if (null !== $with) {
            $method = \call_user_func_array([$method, 'with'], $with);
        }

        if (null === $response) {
            $response = new Response();
        }

        $method->willReturn($response);

        return $this->mockServiceLocator($name, $renderer);
    }

    /**
     * Mocks a service locator.
     *
     * @param string $name
     * @param object $service
     *
     * @return ServiceLocator|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockServiceLocator(string $name, $service): ServiceLocator
    {
        $serviceLocator = $this->createMock(ServiceLocator::class);

        $serviceLocator
            ->expects($this->once())
            ->method('has')
            ->with($name)
            ->willReturn(true)
        ;

        $serviceLocator
            ->expects($this->once())
            ->method('get')
            ->with($name)
            ->willReturn($service)
        ;

        return $serviceLocator;
    }
}

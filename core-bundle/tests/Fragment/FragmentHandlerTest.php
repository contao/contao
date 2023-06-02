<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fragment;

use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentHandler;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\Reference\FragmentReference;
use Contao\CoreBundle\Fragment\UnknownFragmentException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler as BaseFragmentHandler;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;

class FragmentHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testThrowsAnExceptionIfTheFragmentNameIsInvalid(): void
    {
        $fragmentHandler = $this->getFragmentHandler();

        $this->expectException(UnknownFragmentException::class);

        $fragmentHandler->render(new FragmentReference('foo.bar'));
    }

    /**
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

        $fragmentHandler = $this->getFragmentHandler($fragmentRegistry, $renderers, null, $request);
        $fragmentHandler->render($uri);
    }

    public function getRenderingStrategies(): \Generator
    {
        yield ['inline'];
        yield ['esi'];
    }

    /**
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

        $fragmentHandler = $this->getFragmentHandler($fragmentRegistry, $renderers, null, $request);
        $fragmentHandler->render($uri);
    }

    public function getOptions(): \Generator
    {
        yield [['foo' => 'bar']];
        yield [['bar' => 'baz']];
    }

    /**
     * @dataProvider getNonScalarAttributes
     */
    public function testOverridesRenderingOnNonScalarAttributes(string $renderingStrategy, string $expectedRenderer): void
    {
        $uri = new FragmentReference('foo.bar');
        $uri->attributes['foo'] = new \stdClass();

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', new FragmentConfig('foo.bar', $renderingStrategy));

        $request = new Request();

        $renderers = $this->mockServiceLocatorWithRenderer(
            $expectedRenderer,
            [$uri, $request, ['ignore_errors' => false]]
        );

        $fragmentHandler = $this->getFragmentHandler($fragmentRegistry, $renderers, null, $request);
        $fragmentHandler->render($uri);
    }

    public function getNonScalarAttributes(): \Generator
    {
        yield ['esi', 'forward'];
        yield ['hinclude', 'forward'];
        yield ['forward', 'forward'];
        yield ['inline', 'inline'];
    }

    public function testAddsThePageIdFromTheGlobalPageObject(): void
    {
        $uri = new FragmentReference('foo.bar');

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', new FragmentConfig('foo.bar', 'inline', ['foo' => 'bar']));

        $callback = $this->callback(
            static fn () => isset($uri->attributes['pageModel']) && 42 === $uri->attributes['pageModel']
        );

        $renderers = $this->mockServiceLocatorWithRenderer('inline', [$callback]);

        $GLOBALS['objPage'] = $this->mockClassWithProperties(PageModel::class, ['id' => 42]);

        $fragmentHandler = $this->getFragmentHandler($fragmentRegistry, $renderers);
        $fragmentHandler->render($uri);

        unset($GLOBALS['objPage']);
    }

    public function testDoesNotOverrideAGivenPageId(): void
    {
        $uri = new FragmentReference('foo.bar', ['pageModel' => 99]);

        $fragmentRegistry = new FragmentRegistry();
        $fragmentRegistry->add('foo.bar', new FragmentConfig('foo.bar', 'inline', ['foo' => 'bar']));

        $callback = $this->callback(
            static fn () => isset($uri->attributes['pageModel']) && 99 === $uri->attributes['pageModel']
        );

        $renderers = $this->mockServiceLocatorWithRenderer('inline', [$callback]);

        $GLOBALS['objPage'] = $this->mockClassWithProperties(PageModel::class, ['id' => 42]);

        $fragmentHandler = $this->getFragmentHandler($fragmentRegistry, $renderers);
        $fragmentHandler->render($uri);

        unset($GLOBALS['objPage']);
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

        $fragmentHandler = $this->getFragmentHandler($fragmentRegistry, $renderers, $preHandlers);
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

        $fragmentHandler = $this->getFragmentHandler($fragmentRegistry, null, null, null, $baseHandler);
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
        $fragmentHandler = $this->getFragmentHandler($fragmentRegistry, $renderers, null, $request);

        $this->expectException(ResponseException::class);

        $fragmentHandler->render($uri);
    }

    /**
     * @param ServiceLocator<mixed>|null $renderers
     * @param ServiceLocator<mixed>|null $preHandlers
     */
    private function getFragmentHandler(FragmentRegistry $registry = null, ServiceLocator $renderers = null, ServiceLocator $preHandlers = null, Request $request = null, BaseFragmentHandler $fragmentHandler = null): FragmentHandler
    {
        $registry ??= new FragmentRegistry();
        $renderers ??= new ServiceLocator([]);
        $preHandlers ??= new ServiceLocator([]);
        $request ??= new Request();
        $fragmentHandler ??= $this->createMock(BaseFragmentHandler::class);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new FragmentHandler($renderers, $fragmentHandler, $requestStack, $registry, $preHandlers, true);
    }

    /**
     * @return ServiceLocator<mixed>&MockObject
     */
    private function mockServiceLocatorWithRenderer(string $name, array $with = null, Response $response = null): ServiceLocator
    {
        $renderer = $this->createMock(FragmentRendererInterface::class);
        $renderer
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

        $method->willReturn($response ?? new Response());

        return $this->mockServiceLocator($name, $renderer);
    }

    /**
     * @return ServiceLocator<mixed>&MockObject
     */
    private function mockServiceLocator(string $name, object $service): ServiceLocator
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

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

use Contao\CoreBundle\Fragment\FragmentConfig;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\FragmentRenderer;
use Contao\CoreBundle\Fragment\Reference\FragmentReference;
use Contao\CoreBundle\Fragment\UnknownFragmentException;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class FragmentRendererTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $renderer = new FragmentRenderer(
            new FragmentRegistry(),
            $this->createMock(FragmentHandler::class),
            new ServiceLocator([])
        );

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\FragmentRenderer', $renderer);
        $this->assertInstanceOf('Contao\CoreBundle\Fragment\FragmentRendererInterface', $renderer);
    }

    public function testThrowsAnExceptionIfTheFragmentNameIsInvalid(): void
    {
        $renderer = new FragmentRenderer(
            new FragmentRegistry(),
            $this->createMock(FragmentHandler::class),
            new ServiceLocator([])
        );

        $this->expectException(UnknownFragmentException::class);

        $renderer->render(new FragmentReference('foo.bar'));
    }

    /**
     * @param string $renderingStrategy
     *
     * @dataProvider getRenderingStrategies
     */
    public function testPassesTheRendererToTheFragmentHandler(string $renderingStrategy): void
    {
        $uri = new FragmentReference('foo.bar');

        $registry = new FragmentRegistry();
        $registry->add('foo.bar', new FragmentConfig('foo::bar', $renderingStrategy));

        $handler = $this->createMock(FragmentHandler::class);

        $handler
            ->expects($this->once())
            ->method('render')
            ->with($uri, $renderingStrategy)
        ;

        $renderer = new FragmentRenderer($registry, $handler, new ServiceLocator([]));
        $renderer->render($uri);
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
    public function testPassesTheOptionsToTheFragmentHandler(array $options): void
    {
        $uri = new FragmentReference('foo.bar');

        $registry = new FragmentRegistry();
        $registry->add('foo.bar', new FragmentConfig('foo::bar', 'inline', $options));

        $handler = $this->createMock(FragmentHandler::class);

        $handler
            ->expects($this->once())
            ->method('render')
            ->with($uri, 'inline', $options)
        ;

        $renderer = new FragmentRenderer($registry, $handler, new ServiceLocator([]));
        $renderer->render($uri);
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

        $registry = new FragmentRegistry();
        $registry->add('foo.bar', new FragmentConfig('foo::bar', 'inline', ['foo' => 'bar']));

        $handler = $this->createMock(FragmentHandler::class);

        $handler
            ->expects($this->once())
            ->method('render')
            ->with($this->callback(
                function () use ($uri) {
                    return isset($uri->attributes['pageModel']) && 42 === $uri->attributes['pageModel'];
                }
            ))
        ;

        $GLOBALS['objPage'] = new PageModel();
        $GLOBALS['objPage']->id = 42;

        $renderer = new FragmentRenderer($registry, $handler, new ServiceLocator([]));
        $renderer->render($uri);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDoesNotOverridAGivenPageId(): void
    {
        $uri = new FragmentReference('foo.bar', ['pageModel' => 99]);

        $registry = new FragmentRegistry();
        $registry->add('foo.bar', new FragmentConfig('foo::bar', 'inline', ['foo' => 'bar']));

        $handler = $this->createMock(FragmentHandler::class);

        $handler
            ->expects($this->once())
            ->method('render')
            ->with($this->callback(
                function () use ($uri) {
                    return isset($uri->attributes['pageModel']) && 99 === $uri->attributes['pageModel'];
                }
            ))
        ;

        $GLOBALS['objPage'] = new PageModel();
        $GLOBALS['objPage']->id = 42;

        $renderer = new FragmentRenderer($registry, $handler, new ServiceLocator([]));
        $renderer->render($uri);
    }

    public function testExecutesThePreHandlers(): void
    {
        $uri = new FragmentReference('foo.bar');
        $config = new FragmentConfig('foo::bar', 'inline', ['foo' => 'bar']);

        $registry = new FragmentRegistry();
        $registry->add('foo.bar', $config);

        $prehandler = $this->createMock(FragmentPreHandlerInterface::class);

        $prehandler
            ->expects($this->once())
            ->method('preHandleFragment')
            ->with($uri, $config)
        ;

        $serviceLocator = $this->createMock(ServiceLocator::class);

        $serviceLocator
            ->expects($this->once())
            ->method('has')
            ->with('foo.bar')
            ->willReturn(true)
        ;

        $serviceLocator
            ->expects($this->once())
            ->method('get')
            ->with('foo.bar')
            ->willReturn($prehandler)
        ;

        $renderer = new FragmentRenderer($registry, $this->createMock(FragmentHandler::class), $serviceLocator);
        $renderer->render($uri);
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGenerator as ParentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class UrlGeneratorTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_AUTO_ITEM']);
    }

    public function testCanBeInstantiated(): void
    {
        $router = $this->createMock(RouterInterface::class);

        $router
            ->method('generate')
            ->willReturn('foo')
        ;

        $generator = new UrlGenerator($router, $this->mockContaoFramework(), false);

        $this->assertInstanceOf('Contao\CoreBundle\Routing\UrlGenerator', $generator);
    }

    public function testCanWriteTheContext(): void
    {
        $generator = new UrlGenerator(
            new ParentUrlGenerator(new RouteCollection(), new RequestContext()),
            $this->mockContaoFramework(),
            false
        );

        $context = new RequestContext();
        $generator->setContext($context);

        $this->assertSame($context, $generator->getContext());
    }

    public function testGeneratesUrls(): void
    {
        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foobar']))
            ->generate('foobar', ['_locale' => 'de'])
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foobar', '_locale' => 'de']), true)
            ->generate('foobar', ['_locale' => 'de'])
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foobar/test']))
            ->generate('foobar/test')
        ;
    }

    public function testGeneratesUrlsWithoutParameters(): void
    {
        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foobar']))
            ->generate('foobar')
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foobar/test']))
            ->generate('foobar/test')
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foobar/article/test']))
            ->generate('foobar/article/test')
        ;
    }

    public function testOmitsTheIndexFragment(): void
    {
        $this
            ->mockGenerator($this->mockRouterWithContext([], 'contao_index'))
            ->generate('index')
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext([], 'contao_index'), true)
            ->generate('index')
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'index/foobar']))
            ->generate('index/foobar')
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'index/foo/bar']))
            ->generate('index/{foo}', ['foo' => 'bar'])
        ;
    }

    public function testRemovesTheLocaleIfPrependLocaleIsNotSet(): void
    {
        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foobar']))
            ->generate('foobar', ['_locale' => 'en'])
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foobar', '_locale' => 'en']), true)
            ->generate('foobar', ['_locale' => 'en'])
        ;
    }

    public function testReplacesParameters(): void
    {
        $params = ['items' => 'bar', 'article' => 'test'];

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/article/test', 'items' => 'bar']))
            ->generate('foo/{article}', $params)
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar/article/test']))
            ->generate('foo/{items}/{article}', $params)
        ;
    }

    public function testHandlesAutoItems(): void
    {
        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/bar']))
            ->generate('foo/{items}', ['items' => 'bar', 'auto_item' => 'items'])
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/bar/article/test']))
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/bar']))
            ->generate('foo/{items}', ['items' => 'bar'])
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/bar/article/test']))
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;
    }

    public function testIgnoresAutoItemsIfTheyAreDisabled(): void
    {
        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar']), false, false)
            ->generate('foo/{items}', ['items' => 'bar', 'auto_item' => 'items'])
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar/article/test']), false, false)
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar']), false, false)
            ->generate('foo/{items}', ['items' => 'bar'])
        ;

        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar/article/test']), false, false)
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;
    }

    public function testFailsIfAParameterIsMissing(): void
    {
        $router = $this->createMock(UrlGeneratorInterface::class);

        $router
            ->method('getContext')
            ->willReturn(new RequestContext())
        ;

        $this->expectException(MissingMandatoryParametersException::class);

        $this->mockGenerator($router)->generate('foo/{article}');
    }

    public function testReadsTheContextFromTheDomain(): void
    {
        $routes = new RouteCollection();
        $routes->add('contao_index', new Route('/'));

        $generator = new UrlGenerator(
            new ParentUrlGenerator($routes, new RequestContext()),
            $this->mockContaoFramework(),
            false
        );

        $this->assertSame(
            'https://contao.org/',
            $generator->generate(
                'index',
                ['_domain' => 'contao.org:443', '_ssl' => true],
                UrlGeneratorInterface::ABSOLUTE_URL
           )
        );

        $this->assertSame(
            'http://contao.org/',
            $generator->generate('index', ['_domain' => 'contao.org'], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $this->assertSame(
            'http://contao.org/',
            $generator->generate('index', ['_domain' => 'contao.org:80'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * To tests this case, we omit the _ssl parameter and set the scheme to
     * "https" in the context. If the generator still returns a HTTPS URL, we
     * know that the context has not been modified.
     */
    public function testDoesNotModifyTheContextIfThereIsAHostname(): void
    {
        $routes = new RouteCollection();
        $routes->add('contao_index', new Route('/'));

        $context = new RequestContext();
        $context->setHost('contao.org');
        $context->setScheme('https');

        $generator = new UrlGenerator(
            new ParentUrlGenerator($routes, $context),
            $this->mockContaoFramework(),
            false
        );

        $this->assertSame(
            'https://contao.org/',
            $generator->generate('index', ['_domain' => 'contao.org'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    public function testHandlesNonArrayParameters(): void
    {
        $this
            ->mockGenerator($this->mockRouterWithContext(['alias' => 'foo']))
            ->generate('foo', 'bar')
        ;
    }

    /**
     * Mocks an URL generator.
     *
     * @param UrlGeneratorInterface $router
     * @param bool                  $prependLocale
     * @param bool                  $useAutoItem
     *
     * @return UrlGenerator
     */
    private function mockGenerator(UrlGeneratorInterface $router, bool $prependLocale = false, bool $useAutoItem = true): UrlGenerator
    {
        $framework = $this->mockContaoFramework();

        $GLOBALS['TL_CONFIG']['useAutoItem'] = $useAutoItem;

        return new UrlGenerator($router, $framework, $prependLocale);
    }

    /**
     * Mocks a router with context.
     *
     * @param array  $expectedParameters
     * @param string $expectedRoute
     * @param int    $referenceType
     *
     * @return UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockRouterWithContext(array $expectedParameters = [], $expectedRoute = 'contao_frontend', $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): UrlGeneratorInterface
    {
        $router = $this->createMock(UrlGeneratorInterface::class);

        $router
            ->expects($this->once())
            ->method('generate')
            ->with($expectedRoute, $expectedParameters, $referenceType)
        ;

        $router
            ->method('getContext')
            ->willReturn(new RequestContext())
        ;

        return $router;
    }
}

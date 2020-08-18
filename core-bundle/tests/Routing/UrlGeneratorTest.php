<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGenerator as ParentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @group legacy
 */
class UrlGeneratorTest extends TestCase
{
    /**
     * @expectedDeprecation Since contao/core-bundle 4.10: Using the "Contao\CoreBundle\Routing\UrlGenerator" class has been deprecated %s.
     */
    public function testCanWriteTheContext(): void
    {
        $router = new ParentUrlGenerator(new RouteCollection(), new RequestContext());
        $generator = new UrlGenerator($router, $this->mockContaoFramework(), false);

        $context = new RequestContext();
        $generator->setContext($context);

        $this->assertSame($context, $generator->getContext());
    }

    public function testGeneratesUrls(): void
    {
        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foobar']))
            ->generate('foobar', ['_locale' => 'de'])
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foobar', '_locale' => 'de']), true)
            ->generate('foobar', ['_locale' => 'de'])
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foobar/test']))
            ->generate('foobar/test')
        ;
    }

    public function testGeneratesUrlsWithoutParameters(): void
    {
        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foobar']))
            ->generate('foobar')
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foobar/test']))
            ->generate('foobar/test')
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foobar/article/test']))
            ->generate('foobar/article/test')
        ;
    }

    public function testOmitsTheIndexFragment(): void
    {
        $this
            ->getUrlGenerator($this->mockRouterWithContext([], 'contao_index'))
            ->generate('index')
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext([], 'contao_index'), true)
            ->generate('index')
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'index/foobar']))
            ->generate('index/foobar')
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'index/foo/bar']))
            ->generate('index/{foo}', ['foo' => 'bar'])
        ;
    }

    public function testRemovesTheLocaleIfPrependLocaleIsNotSet(): void
    {
        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foobar']))
            ->generate('foobar', ['_locale' => 'en'])
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foobar', '_locale' => 'en']), true)
            ->generate('foobar', ['_locale' => 'en'])
        ;
    }

    public function testReplacesParameters(): void
    {
        $params = ['items' => 'bar', 'article' => 'test'];

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/article/test', 'items' => 'bar']))
            ->generate('foo/{article}', $params)
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar/article/test']), false, false)
            ->generate('foo/{items}/{article}', $params)
        ;
    }

    public function testHandlesAutoItems(): void
    {
        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/bar']))
            ->generate('foo/{items}', ['items' => 'bar', 'auto_item' => 'items'])
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/bar/article/test']))
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/bar']))
            ->generate('foo/{items}', ['items' => 'bar'])
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/bar/article/test']))
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;

        unset($GLOBALS['TL_AUTO_ITEM']);
    }

    public function testIgnoresAutoItemsIfTheyAreDisabled(): void
    {
        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar']), false, false)
            ->generate('foo/{items}', ['items' => 'bar', 'auto_item' => 'items'])
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar/article/test']), false, false)
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar']), false, false)
            ->generate('foo/{items}', ['items' => 'bar'])
        ;

        $this
            ->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo/items/bar/article/test']), false, false)
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;

        unset($GLOBALS['TL_AUTO_ITEM']);
    }

    public function testFailsIfAParameterIsMissing(): void
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router
            ->method('getContext')
            ->willReturn(new RequestContext())
        ;

        $this->expectException(MissingMandatoryParametersException::class);

        $this->getUrlGenerator($router)->generate('foo/{article}');
    }

    public function testReadsTheContextFromTheDomain(): void
    {
        $routes = new RouteCollection();
        $routes->add('contao_index', new Route('/'));

        $router = new ParentUrlGenerator($routes, new RequestContext());
        $generator = new UrlGenerator($router, $this->mockContaoFramework(), false);

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

        $router = new ParentUrlGenerator($routes, $context);
        $generator = new UrlGenerator($router, $this->mockContaoFramework(), false);

        $this->assertSame(
            'https://contao.org/',
            $generator->generate('index', ['_domain' => 'contao.org'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testHandlesNonArrayParameters(): void
    {
        $generator = $this->getUrlGenerator($this->mockRouterWithContext(['alias' => 'foo']));

        /* @phpstan-ignore-next-line */
        $generator->generate('foo', 'bar');
    }

    private function getUrlGenerator(UrlGeneratorInterface $router, bool $prependLocale = false, bool $useAutoItem = true): UrlGenerator
    {
        $framework = $this->mockContaoFramework();

        $GLOBALS['TL_CONFIG']['useAutoItem'] = $useAutoItem;

        return new UrlGenerator($router, $framework, $prependLocale);
    }

    /**
     * @return UrlGeneratorInterface&MockObject
     */
    private function mockRouterWithContext(array $expectedParameters = [], string $expectedRoute = 'contao_frontend', int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): UrlGeneratorInterface
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

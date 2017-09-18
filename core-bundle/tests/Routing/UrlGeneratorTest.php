<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGenerator as ParentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the UrlGenerator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class UrlGeneratorTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        unset($GLOBALS['TL_AUTO_ITEM']);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf(
            'Contao\CoreBundle\Routing\UrlGenerator',
            new UrlGenerator($this->mockRouter('foo'), $this->mockContaoFramework(), false)
        );
    }

    /**
     * Tests the setContext() method.
     */
    public function testCanWriteTheContext()
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

    /**
     * Tests the router.
     */
    public function testGeneratesUrls()
    {
        $this
            ->getGenerator($this->getRouter(['alias' => 'foobar']))
            ->generate('foobar', ['_locale' => 'de'])
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foobar', '_locale' => 'de']), true)
            ->generate('foobar', ['_locale' => 'de'])
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foobar/test']))
            ->generate('foobar/test')
        ;
    }

    /**
     * Tests the router without parameters.
     */
    public function testGeneratesUrlsWithoutParameters()
    {
        $this
            ->getGenerator($this->getRouter(['alias' => 'foobar']))
            ->generate('foobar')
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foobar/test']))
            ->generate('foobar/test')
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foobar/article/test']))
            ->generate('foobar/article/test')
        ;
    }

    /**
     * Tests that the index fragment is omitted.
     */
    public function testOmitsTheIndexFragment()
    {
        $this
            ->getGenerator($this->getRouter([], 'contao_index'))
            ->generate('index')
        ;

        $this
            ->getGenerator($this->getRouter([], 'contao_index'), true)
            ->generate('index')
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'index/foobar']))
            ->generate('index/foobar')
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'index/foo/bar']))
            ->generate('index/{foo}', ['foo' => 'bar'])
        ;
    }

    /**
     * Tests that the locale is removed if prepend_locale is not set.
     */
    public function testRemovesTheLocaleIfPrependLocaleIsNotSet()
    {
        $this
            ->getGenerator($this->getRouter(['alias' => 'foobar']))
            ->generate('foobar', ['_locale' => 'en'])
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foobar', '_locale' => 'en']), true)
            ->generate('foobar', ['_locale' => 'en'])
        ;
    }

    /**
     * Tests the parameter replacement.
     */
    public function testReplacesParameters()
    {
        $params = ['items' => 'bar', 'article' => 'test'];

        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/article/test', 'items' => 'bar']))
            ->generate('foo/{article}', $params)
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/items/bar/article/test']))
            ->generate('foo/{items}/{article}', $params)
        ;
    }

    /**
     * Tests the auto_item support.
     */
    public function testHandlesAutoItems()
    {
        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/bar']))
            ->generate('foo/{items}', ['items' => 'bar', 'auto_item' => 'items'])
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/bar/article/test']))
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/bar']))
            ->generate('foo/{items}', ['items' => 'bar'])
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/bar/article/test']))
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;
    }

    /**
     * Tests the router with auto_item being disabled.
     */
    public function testIgnoresAutoItemsIfTheyAreDisabled()
    {
        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/items/bar']), false, false)
            ->generate('foo/{items}', ['items' => 'bar', 'auto_item' => 'items'])
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/items/bar/article/test']), false, false)
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/items/bar']), false, false)
            ->generate('foo/{items}', ['items' => 'bar'])
        ;

        $this
            ->getGenerator($this->getRouter(['alias' => 'foo/items/bar/article/test']), false, false)
            ->generate('foo/{items}/{article}', ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items'])
        ;
    }

    /**
     * Tests that an exception is thrown if a parameter is missing.
     */
    public function testFailsIfAParameterIsMissing()
    {
        $router = $this->createMock(UrlGeneratorInterface::class);

        $router
            ->method('getContext')
            ->willReturn(new RequestContext())
        ;

        $this->expectException(MissingMandatoryParametersException::class);

        $this->getGenerator($router)->generate('foo/{article}');
    }

    /**
     * Tests setting the context from a domain.
     */
    public function testReadsTheContextFromTheDomain()
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
     * Tests that the context is not modified if the hostname is set.
     *
     * To tests this case, we omit the _ssl parameter and set the scheme to "https" in the context. If the
     * generator still returns a HTTPS URL, we know that the context has not been modified.
     */
    public function testDoesNotModifyTheContextIfThereIsAHostname()
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

    /**
     * Tests the generator with non-array parameters.
     */
    public function testHandlesNonArrayParameters()
    {
        $this
            ->getGenerator($this->getRouter(['alias' => 'foo']))
            ->generate('foo', 'bar')
        ;
    }

    /**
     * Returns an UrlGenerator object.
     *
     * @param UrlGeneratorInterface $router
     * @param bool                  $prependLocale
     * @param bool                  $useAutoItem
     *
     * @return UrlGenerator
     */
    private function getGenerator(UrlGeneratorInterface $router, $prependLocale = false, $useAutoItem = true)
    {
        $configAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->getMock()
        ;

        $configAdapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('get')
            ->willReturnCallback(function ($key) use ($useAutoItem) {
                switch ($key) {
                    case 'useAutoItem':
                        return $useAutoItem;

                    case 'timeZone':
                        return 'Europe/Berlin';

                    default:
                        return null;
                }
            })
        ;

        return new UrlGenerator(
            $router,
            $this->mockContaoFramework(null, null, [Config::class => $configAdapter]),
            $prependLocale
        );
    }

    /**
     * Returns the router object.
     *
     * @param array  $expectedParameters
     * @param string $expectedRoute
     * @param int    $referenceType
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface
     */
    private function getRouter(array $expectedParameters = [], $expectedRoute = 'contao_frontend', $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
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

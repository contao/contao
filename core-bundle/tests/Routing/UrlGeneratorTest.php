<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Routing;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Test\TestCase;
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
        unset($GLOBALS['TL_AUTO_ITEM']);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Routing\UrlGenerator', $this->getGenerator());
    }

    /**
     * Tests the setContext() method.
     */
    public function testSetContext()
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
    public function testRoute()
    {
        $this->assertEquals('contao_frontend', $this->getGenerator(false, 0)->generate('foobar'));
        $this->assertEquals('contao_frontend', $this->getGenerator(true, 0)->generate('foobar'));
        $this->assertEquals('contao_frontend', $this->getGenerator(false, 0)->generate('foobar/test'));
    }

    /**
     * Tests the router without parameters.
     */
    public function testWithoutParameters()
    {
        $this->assertEquals('foobar', $this->getGenerator()->generate('foobar')['alias']);
        $this->assertEquals('foobar/test', $this->getGenerator()->generate('foobar/test')['alias']);
        $this->assertEquals('foobar/article/test', $this->getGenerator()->generate('foobar/article/test')['alias']);
    }

    /**
     * Tests that the index fragment is omitted.
     */
    public function testIndex()
    {
        $this->assertEquals('contao_index', $this->getGenerator(false, 0)->generate('index'));
        $this->assertEquals('contao_index', $this->getGenerator(true, 0)->generate('index'));
        $this->assertArrayNotHasKey('alias', $this->getGenerator()->generate('index'));

        $this->assertEquals('contao_frontend', $this->getGenerator(false, 0)->generate('index/foobar'));
        $this->assertArrayHasKey('alias', $this->getGenerator()->generate('index/foobar'));

        $this->assertEquals(
            'contao_frontend',
            $this->getGenerator(false, 0)->generate('index/{foo}', ['foo' => 'bar'])
        );

        $this->assertArrayHasKey('alias', $this->getGenerator()->generate('index/{foo}', ['foo' => 'bar']));
        $this->assertEquals('index/foo/bar', $this->getGenerator()->generate('index/{foo}', ['foo' => 'bar'])['alias']);
    }

    /**
     * Tests that the locale is removed if prepend_locale is not set.
     */
    public function testRemovesLocale()
    {
        $params = $this->getGenerator(false)->generate('foobar', ['_locale' => 'en']);

        $this->assertArrayNotHasKey('_locale', $params);

        $params = $this->getGenerator(true)->generate('foobar', ['_locale' => 'en']);

        $this->assertArrayHasKey('_locale', $params);
    }

    /**
     * Tests the parameter replacement.
     */
    public function testReplaceParameters()
    {
        $params = ['items' => 'bar', 'article' => 'test'];

        $result = $this->getGenerator()->generate('foo/{article}', $params);

        $this->assertEquals('foo/article/test', $result['alias']);
        $this->assertArrayNotHasKey('article', $result);
        $this->assertArrayHasKey('items', $result);

        $result = $this->getGenerator()->generate('foo/{items}/{article}', $params);

        $this->assertEquals('foo/items/bar/article/test', $result['alias']);
        $this->assertArrayNotHasKey('article', $result);
        $this->assertArrayNotHasKey('items', $result);
    }

    /**
     * Tests the auto_item support.
     */
    public function testAutoItem()
    {
        $this->assertEquals(
            'foo/bar',
            $this->getGenerator()->generate(
                'foo/{items}',
                ['items' => 'bar', 'auto_item' => 'items']
            )['alias']
        );

        $this->assertEquals(
            'foo/bar/article/test',
            $this->getGenerator()->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this->assertEquals(
            'foo/bar',
            $this->getGenerator()->generate(
                'foo/{items}',
                ['items' => 'bar']
            )['alias']
        );

        $this->assertEquals(
            'foo/bar/article/test',
            $this->getGenerator()->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );
    }

    /**
     * Tests the router with auto_item being disabled.
     */
    public function testAutoItemDisabled()
    {
        $this->assertEquals(
            'foo/items/bar',
            $this->getGenerator(false, 1, false)->generate(
                'foo/{items}',
                ['items' => 'bar', 'auto_item' => 'items']
            )['alias']
        );

        $this->assertEquals(
            'foo/items/bar/article/test',
            $this->getGenerator(false, 1, false)->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this->assertEquals(
            'foo/items/bar',
            $this->getGenerator(false, 1, false)->generate(
                'foo/{items}',
                ['items' => 'bar']
            )['alias']
        );

        $this->assertEquals(
            'foo/items/bar/article/test',
            $this->getGenerator(false, 1, false)->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );
    }

    /**
     * Tests that an exception is thrown if a parameter is missing.
     *
     * @expectedException \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     */
    public function testThrowsExceptionOnMissingParameter()
    {
        $this->getGenerator()->generate('foo/{article}');
    }

    /**
     * Tests setting the context from a domain.
     */
    public function testSetContextFromDomain()
    {
        $routes = new RouteCollection();
        $routes->add('contao_index', new Route('/'));

        $generator = new UrlGenerator(
            new ParentUrlGenerator($routes, new RequestContext()),
            $this->mockContaoFramework(),
            false
        );

        $this->assertEquals(
            'https://contao.org/',
            $generator->generate(
                'index',
                ['_domain' => 'contao.org:443', '_ssl' => true],
                UrlGeneratorInterface::ABSOLUTE_URL
           )
        );

        $this->assertEquals(
            'http://contao.org/',
            $generator->generate(
                'index',
                ['_domain' => 'contao.org'],
                UrlGeneratorInterface::ABSOLUTE_URL
           )
        );

        $this->assertEquals(
            'http://contao.org/',
            $generator->generate(
                'index',
                ['_domain' => 'contao.org:80'],
                UrlGeneratorInterface::ABSOLUTE_URL
           )
        );
    }

    /**
     * Tests that the context is not modified if the hostname is set.
     *
     * To tests this case, we omit the _ssl parameter and set the scheme to "https" in the context. If the
     * generator still returns a HTTPS URL, we know that the context has not been modified.
     */
    public function testContextNotModifiedIfHostnameIsSet()
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

        $this->assertEquals(
            'https://contao.org/',
            $generator->generate(
                'index',
                ['_domain' => 'contao.org'],
                UrlGeneratorInterface::ABSOLUTE_URL
           )
        );
    }

    /**
     * Tests the generator with non-array parameters.
     */
    public function testWithNonArrayParameters()
    {
        $this->assertEquals('foo', $this->getGenerator()->generate('foo', 'bar')['alias']);
    }

    /**
     * Returns an UrlGenerator object.
     *
     * @param bool $prependLocale
     * @param int  $returnArgument
     * @param bool $useAutoItem
     *
     * @return UrlGenerator
     */
    private function getGenerator($prependLocale = false, $returnArgument = 1, $useAutoItem = true)
    {
        /** @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturnArgument($returnArgument)
        ;

        $router
            ->expects($this->any())
            ->method('getContext')
            ->willReturn(new RequestContext())
        ;

        /** @var Adapter|\PHPUnit_Framework_MockObject_MockObject $configAdapter */
        $configAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $configAdapter
            ->expects($this->any())
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
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
            $this->mockContaoFramework(null, null, ['Contao\Config' => $configAdapter]),
            $prependLocale
        );
    }
}

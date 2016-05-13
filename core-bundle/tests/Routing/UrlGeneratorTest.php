<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Routing;

use Contao\CoreBundle\Routing\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Tests the UrlGenerator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class UrlGeneratorTest extends \PHPUnit_Framework_TestCase
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
        $this->assertInstanceOf('Contao\CoreBundle\Routing\UrlGenerator', $this->generator());
    }

    /**
     * Tests the router.
     */
    public function testRoute()
    {
        $this->assertEquals('contao_frontend', $this->generator(false, 0)->generate('foobar'));
        $this->assertEquals('contao_frontend', $this->generator(true, 0)->generate('foobar'));
        $this->assertEquals('contao_frontend', $this->generator(false, 0)->generate('foobar/test'));
    }

    /**
     * Tests the router without parameters.
     */
    public function testWithoutParameters()
    {
        $this->assertEquals('foobar', $this->generator()->generate('foobar')['alias']);
        $this->assertEquals('foobar/test', $this->generator()->generate('foobar/test')['alias']);
        $this->assertEquals('foobar/article/test', $this->generator()->generate('foobar/article/test')['alias']);
    }

    /**
     * Tests that the index fragment is omitted.
     */
    public function testIndex()
    {
        $this->assertEquals('contao_index', $this->generator(false, 0)->generate('index'));
        $this->assertEquals('contao_index', $this->generator(true, 0)->generate('index'));
        $this->assertArrayNotHasKey('alias', $this->generator()->generate('index'));

        $this->assertEquals('contao_frontend', $this->generator(false, 0)->generate('index/foobar'));
        $this->assertArrayHasKey('alias', $this->generator()->generate('index/foobar'));

        $this->assertEquals('contao_frontend', $this->generator(false, 0)->generate('index/{foo}', ['foo' => 'bar']));
        $this->assertArrayHasKey('alias', $this->generator()->generate('index/{foo}', ['foo' => 'bar']));
        $this->assertEquals('index/foo/bar', $this->generator()->generate('index/{foo}', ['foo' => 'bar'])['alias']);
    }

    /**
     * Tests that the locale is removed if prepend_locale is not set.
     */
    public function testRemovesLocale()
    {
        $params = $this->generator(false)->generate('foobar', ['_locale' => 'en']);

        $this->assertArrayNotHasKey('_locale', $params);

        $params = $this->generator(true)->generate('foobar', ['_locale' => 'en']);

        $this->assertArrayHasKey('_locale', $params);
    }

    /**
     * Tests the parameter replacement.
     */
    public function testReplaceParameters()
    {
        $params = ['items' => 'bar', 'article' => 'test'];

        $result = $this->generator()->generate('foo/{article}', $params);

        $this->assertEquals('foo/article/test', $result['alias']);
        $this->assertArrayNotHasKey('article', $result);
        $this->assertArrayHasKey('items', $result);

        $result = $this->generator()->generate('foo/{items}/{article}', $params);

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
            $this->generator()->generate('foo/{items}', ['items' => 'bar', 'auto_item' => 'items'])['alias']
        );

        $this->assertEquals(
            'foo/bar/article/test',
            $this->generator()->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this->assertEquals('foo/bar', $this->generator()->generate('foo/{items}', ['items' => 'bar'])['alias']);

        $this->assertEquals(
            'foo/bar/article/test',
            $this->generator()->generate(
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
        $this->generator()->generate('foo/{article}');
    }

    /**
     * Returns an UrlGenerator object.
     *
     * @param bool $prependLocale
     * @param int  $returnArgument
     *
     * @return UrlGenerator
     */
    private function generator($prependLocale = false, $returnArgument = 1)
    {
        return new UrlGenerator($this->mockRouter($returnArgument), $prependLocale);
    }

    /**
     * Mocks a router object.
     *
     * @param int $returnArgument
     *
     * @return UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockRouter($returnArgument = 0)
    {
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

        return $router;
    }
}

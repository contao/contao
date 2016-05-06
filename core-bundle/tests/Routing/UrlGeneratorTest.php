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
use Symfony\Component\Routing\RequestContext;

/**
 * Tests the UrlGenerator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class UrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
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

    public function testRoute()
    {
        $this->assertEquals('contao_index', $this->generator(false, 0)->generate('index'));
        $this->assertEquals('contao_index', $this->generator(true, 0)->generate('index'));
        $this->assertEquals('contao_frontend', $this->generator(false, 0)->generate('foobar'));
        $this->assertEquals('contao_frontend', $this->generator(true, 0)->generate('foobar'));
        $this->assertEquals('contao_frontend', $this->generator(false, 0)->generate('foobar/test'));
    }

    public function testWithoutParameters()
    {
        $this->assertEquals('foobar', $this->generator()->generate('foobar')['alias']);
        $this->assertEquals('foobar/test', $this->generator()->generate('foobar/test')['alias']);
        $this->assertEquals('foobar/article/test', $this->generator()->generate('foobar/article/test')['alias']);
    }

    public function testRemovesLocale()
    {
        $params = $this->generator(false)->generate('foobar', ['_locale' => 'en']);

        $this->assertArrayNotHasKey('_locale', $params);

        $params = $this->generator(true)->generate('foobar', ['_locale' => 'en']);

        $this->assertArrayHasKey('_locale', $params);
    }

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

        $GLOBALS['TL_AUTO_ITEM'] = ['items'];
        $this->assertEquals('foo/bar', $this->generator()->generate('foo/{items}', ['items' => 'bar'])['alias']);

        $GLOBALS['TL_AUTO_ITEM'] = ['items', 'article'];
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
     * @expectedException \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     */
    public function testThrowsExceptionOnMissingParameter()
    {
        $this->generator()->generate('foo/{article}');
    }


    private function generator($prependLocale = false, $returnArgument = 1)
    {
        return new UrlGenerator($this->mockRouter($returnArgument), $prependLocale);
    }

    private function mockRouter($returnArgument = 0)
    {
        $router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $router->method('generate')->willReturnArgument($returnArgument);

        $context = new RequestContext();
        $router->method('getContext')->willReturn($context);

        return $router;
    }
}

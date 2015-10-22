<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Routing;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Routing\FrontendLoader;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the FrontendLoader class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendLoaderTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $loader = new FrontendLoader(false);

        $this->assertInstanceOf('Contao\CoreBundle\Routing\FrontendLoader', $loader);
    }

    /**
     * Tests the supports() method.
     */
    public function testSupports()
    {
        $loader = new FrontendLoader(false);

        $this->assertTrue($loader->supports('.', 'contao_frontend'));
    }

    /**
     * Tests that the dynamic routes have the correct scope.
     */
    public function testContainerScope()
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');

        $this->assertEquals(
            ContaoCoreBundle::SCOPE_FRONTEND,
            $collection->get('contao_frontend')->getDefault('_scope')
        );

        $this->assertEquals(
            ContaoCoreBundle::SCOPE_FRONTEND,
            $collection->get('contao_index')->getDefault('_scope')
        );
    }

    /**
     * Tests that the dynamic routes are mapped to the correct controller.
     */
    public function testController()
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');

        $this->assertEquals(
            'ContaoCoreBundle:Frontend:index',
            $collection->get('contao_frontend')->getDefault('_controller')
        );

        $this->assertEquals(
            'ContaoCoreBundle:Frontend:index',
            $collection->get('contao_index')->getDefault('_controller')
        );
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     */
    public function testGenerateFrontendWithMissingAlias()
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $router->generate('contao_frontend');
    }

    /**
     * Tests generating  generating the "contao_frontend" route without locale.
     */
    public function testGenerateFrontendWithoutLocale()
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertEquals(
            '/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar'])
        );

        $this->assertEquals(
            '/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar', '_locale' => 'en'])
        );
    }

    /**
     * Tests generating  generating the "contao_frontend" route with locale.
     */
    public function testGenerateFrontendWithLocale()
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertEquals(
            '/en/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar', '_locale' => 'en'])
        );
    }

    /**
     * Tests generating the "contao_frontend" route with missing locale.
     *
     * @expectedException \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     */
    public function testGenerateFrontendWithMissingLocale()
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $router->generate('contao_frontend', ['alias' => 'foobar']);
    }

    /**
     * Tests generating the "contao_index" route without locale.
     */
    public function testGenerateIndexWithoutLocale()
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertEquals(
            '/',
            $router->generate('contao_index')
        );

        $this->assertEquals(
            '/',
            $router->generate('contao_index', ['_locale' => 'en'])
        );
    }

    /**
     * Tests generating the "contao_index" route with locale.
     */
    public function testGenerateIndexWithLocale()
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertEquals(
            '/en/',
            $router->generate('contao_index', ['_locale' => 'en'])
        );
    }

    /**
     * Tests generating the "contao_index" route with missing locale.
     *
     * @expectedException \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     */
    public function testGenerateIndexWithMissingLocale()
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $router->generate('contao_index');
    }

    /**
     * Generates a router using the given RouteCollection.
     *
     * @param RouteCollection $collection The route collection
     * @param string          $urlSuffix  The URL suffix
     *
     * @return Router The router object
     */
    private function getRouter(RouteCollection $collection, $urlSuffix = '.html')
    {
        $loader = $this->getMock('Symfony\Component\Config\Loader\LoaderInterface');

        $loader
            ->expects($this->any())
            ->method('load')
            ->willReturn($collection)
        ;

        /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            ['get', 'getParameter']
        );

        $container
            ->expects($this->any())
            ->method('getParameter')
            ->with('contao.url_suffix')
            ->willReturn($urlSuffix)
        ;

        $container
            ->expects($this->any())
            ->method('get')
            ->with('routing.loader')
            ->willReturn($loader)
        ;

        return new Router($container, '');
    }
}

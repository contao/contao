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
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
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

        $this->assertInstanceOf('Contao\\CoreBundle\\Routing\\FrontendLoader', $loader);
    }

    /**
     * Ensures that the loader supports "contao_frontend".
     */
    public function testSupportsContaoFrontend()
    {
        $loader = new FrontendLoader(false);

        $this->assertTrue($loader->supports('.', 'contao_frontend'));
    }

    public function testContainerScope()
    {
        $loader     = new FrontendLoader(false);
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

    public function testController()
    {
        $loader     = new FrontendLoader(false);
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
        $loader     = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router     = $this->getRouter($collection);

        $router->generate('contao_frontend');
    }

    public function testGenerateFrontendWithoutLocale()
    {
        $loader     = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router     = $this->getRouter($collection);

        $this->assertEquals(
            '/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar'])
        );

        $this->assertEquals(
            '/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar', '_locale' => 'en'])
        );
    }

    public function testGenerateFrontendWithLocale()
    {
        $loader     = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router     = $this->getRouter($collection);

        $this->assertEquals(
            '/en/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar', '_locale' => 'en'])
        );
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     */
    public function testGenerateFrontendWithMissingLocale()
    {
        $loader     = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router     = $this->getRouter($collection);

        $router->generate('contao_frontend', ['alias' => 'foobar']);
    }

    public function testGenerateIndexWithoutLocale()
    {
        $loader     = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router     = $this->getRouter($collection);

        $this->assertEquals(
            '/',
            $router->generate('contao_index')
        );

        $this->assertEquals(
            '/',
            $router->generate('contao_index', ['_locale' => 'en'])
        );
    }

    public function testGenerateIndexWithLocale()
    {
        $loader     = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router     = $this->getRouter($collection);

        $this->assertEquals(
            '/en/',
            $router->generate('contao_index', ['_locale' => 'en'])
        );
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     */
    public function testGenerateIndexWithMissingLocale()
    {
        $loader     = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router     = $this->getRouter($collection);

        $router->generate('contao_index');
    }

    /**
     * Generates a router using the given RouteCollection.
     *
     * @param RouteCollection $collection
     * @param string          $urlSuffix
     *
     * @return Router
     */
    private function getRouter(RouteCollection $collection, $urlSuffix = '.html')
    {
        $loader = $this->getMock(
            'Symfony\\Component\\Config\\Loader\\LoaderInterface'
        );

        $loader
            ->expects($this->any())
            ->method('load')
            ->willReturn($collection)
        ;

        $container = $this->getMock(
            'Symfony\\Component\\DependencyInjection\\Container',
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

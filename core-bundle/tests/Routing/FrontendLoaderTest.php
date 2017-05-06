<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Routing\FrontendLoader;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the FrontendLoader class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendLoaderTest extends \PHPUnit_Framework_TestCase
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

        $this->assertSame(
            ContaoCoreBundle::SCOPE_FRONTEND,
            $collection->get('contao_frontend')->getDefault('_scope')
        );

        $this->assertSame(
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

        $this->assertSame(
            'ContaoCoreBundle:Frontend:index',
            $collection->get('contao_frontend')->getDefault('_controller')
        );

        $this->assertSame(
            'ContaoCoreBundle:Frontend:index',
            $collection->get('contao_index')->getDefault('_controller')
        );
    }

    public function testGenerateFrontendWithMissingAlias()
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->setExpectedException(MissingMandatoryParametersException::class);

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

        $this->assertSame(
            '/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar'])
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

        $this->assertSame(
            '/en/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar', '_locale' => 'en'])
        );
    }

    /**
     * Tests generating the "contao_frontend" route with missing locale.
     */
    public function testGenerateFrontendWithMissingLocale()
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->setExpectedException(MissingMandatoryParametersException::class);

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

        $this->assertSame(
            '/',
            $router->generate('contao_index')
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

        $this->assertSame(
            '/en/',
            $router->generate('contao_index', ['_locale' => 'en'])
        );
    }

    /**
     * Tests generating the "contao_index" route with missing locale.
     */
    public function testGenerateIndexWithMissingLocale()
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->setExpectedException(MissingMandatoryParametersException::class);

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

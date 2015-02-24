<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Routing;

use Contao\Config;
use Contao\CoreBundle\Routing\FrontendLoader;

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
        $loader = new FrontendLoader();

        $this->assertInstanceOf('Contao\CoreBundle\Routing\FrontendLoader', $loader);
    }

    /**
     * Tests with URL suffix and without language.
     */
    public function testLoadWithoutLanguage()
    {
        Config::set('urlSuffix', '.html');
        Config::set('addLanguageToUrl', false);

        $loader     = new FrontendLoader();
        $collection = $loader->load('.', 'bundles');

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);

        $routes = $collection->all();

        // contao_default
        $this->assertArrayHasKey('contao_default', $routes);
        $this->assertEquals('/{alias}.{_format}', $routes['contao_default']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_default']->getDefault('_controller'));
        $this->assertEquals('html', $routes['contao_default']->getDefault('_format'));
        $this->assertEquals('.*', $routes['contao_default']->getRequirement('alias'));
        $this->assertEquals('html', $routes['contao_default']->getRequirement('_format'));

        // contao_root
        $this->assertArrayHasKey('contao_root', $routes);
        $this->assertEquals('/', $routes['contao_root']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_root']->getDefault('_controller'));
        $this->assertEquals('html', $routes['contao_root']->getDefault('_format'));
    }

    /**
     * Tests with URL suffix and with language.
     */
    public function testLoadWitLanguage()
    {
        Config::set('urlSuffix', '.html');
        Config::set('addLanguageToUrl', true);

        $loader     = new FrontendLoader();
        $collection = $loader->load('.', 'bundles');

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);

        $routes = $collection->all();

        // contao_locale
        $this->assertArrayHasKey('contao_locale', $routes);
        $this->assertEquals('/{_locale}/{alias}.{_format}', $routes['contao_locale']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_locale']->getDefault('_controller'));
        $this->assertEquals('html', $routes['contao_locale']->getDefault('_format'));
        $this->assertEquals('.*', $routes['contao_locale']->getRequirement('alias'));
        $this->assertEquals('html', $routes['contao_locale']->getRequirement('_format'));
        $this->assertEquals('[a-z]{2}(\-[A-Z]{2})?', $routes['contao_locale']->getRequirement('_locale'));

        // contao_default
        $this->assertArrayHasKey('contao_default', $routes);
        $this->assertEquals('/{alias}.{_format}', $routes['contao_default']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_default']->getDefault('_controller'));
        $this->assertEquals('html', $routes['contao_default']->getDefault('_format'));
        $this->assertEquals('.*', $routes['contao_default']->getRequirement('alias'));
        $this->assertEquals('html', $routes['contao_default']->getRequirement('_format'));
        $this->assertEquals('[a-z]{2}(\-[A-Z]{2})?', $routes['contao_default']->getRequirement('_locale'));

        // contao_root
        $this->assertArrayHasKey('contao_root', $routes);
        $this->assertEquals('/', $routes['contao_root']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_root']->getDefault('_controller'));
        $this->assertEquals('html', $routes['contao_root']->getDefault('_format'));
    }

    /**
     * Tests without URL suffix and without language.
     */
    public function testLoadWithoutLanguageAndWithoutSuffix()
    {
        Config::set('urlSuffix', '');
        Config::set('addLanguageToUrl', false);

        $loader     = new FrontendLoader();
        $collection = $loader->load('.', 'bundles');

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);

        $routes = $collection->all();

        // contao_default
        $this->assertArrayHasKey('contao_default', $routes);
        $this->assertEquals('/{alias}', $routes['contao_default']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_default']->getDefault('_controller'));
        $this->assertEquals('', $routes['contao_default']->getDefault('_format'));
        $this->assertEquals('.*', $routes['contao_default']->getRequirement('alias'));
        $this->assertEquals('', $routes['contao_default']->getRequirement('_format'));

        // contao_root
        $this->assertArrayHasKey('contao_root', $routes);
        $this->assertEquals('/', $routes['contao_root']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_root']->getDefault('_controller'));
        $this->assertEquals('', $routes['contao_root']->getDefault('_format'));
    }

    /**
     * Tests without URL suffix and with language.
     */
    public function testLoadWithLanguageAndWithoutSuffix()
    {
        Config::set('urlSuffix', '');
        Config::set('addLanguageToUrl', true);

        $loader     = new FrontendLoader();
        $collection = $loader->load('.', 'bundles');

        $this->assertInstanceOf('Symfony\Component\Routing\RouteCollection', $collection);

        $routes = $collection->all();

        // contao_locale
        $this->assertArrayHasKey('contao_locale', $routes);
        $this->assertEquals('/{_locale}/{alias}', $routes['contao_locale']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_locale']->getDefault('_controller'));
        $this->assertEquals('', $routes['contao_locale']->getDefault('_format'));
        $this->assertEquals('.*', $routes['contao_locale']->getRequirement('alias'));
        $this->assertEquals('', $routes['contao_locale']->getRequirement('_format'));
        $this->assertEquals('[a-z]{2}(\-[A-Z]{2})?', $routes['contao_locale']->getRequirement('_locale'));

        // contao_default
        $this->assertArrayHasKey('contao_default', $routes);
        $this->assertEquals('/{alias}', $routes['contao_default']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_default']->getDefault('_controller'));
        $this->assertEquals('', $routes['contao_default']->getDefault('_format'));
        $this->assertEquals('.*', $routes['contao_default']->getRequirement('alias'));
        $this->assertEquals('', $routes['contao_default']->getRequirement('_format'));
        $this->assertEquals('[a-z]{2}(\-[A-Z]{2})?', $routes['contao_default']->getRequirement('_locale'));

        // contao_root
        $this->assertArrayHasKey('contao_root', $routes);
        $this->assertEquals('/', $routes['contao_root']->getPath());
        $this->assertEquals('ContaoCoreBundle:Frontend:index', $routes['contao_root']->getDefault('_controller'));
        $this->assertEquals('', $routes['contao_root']->getDefault('_format'));
    }

    /**
     * Ensures that the loader supports "bundles".
     */
    public function testSupportsContaoFrontend()
    {
        $loader = new FrontendLoader();

        $this->assertTrue($loader->supports('.', 'bundles'));
    }
}

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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Routing\FrontendLoader;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\RouteCollection;

/**
 * @group legacy
 */
class FrontendLoaderTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expectDeprecation('%sUsing the "%sFrontendLoader" class has been deprecated%s');
    }

    public function testSupportsTheContaoFrontEndRoute(): void
    {
        $loader = new FrontendLoader(false);

        $this->assertTrue($loader->supports('.', 'contao_frontend'));
    }

    public function testReturnsTheCorrectScope(): void
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $frontend = $collection->get('contao_frontend');

        $this->assertNotNull($frontend);
        $this->assertSame(ContaoCoreBundle::SCOPE_FRONTEND, $frontend->getDefault('_scope'));

        $index = $collection->get('contao_index');

        $this->assertNotNull($index);
        $this->assertSame(ContaoCoreBundle::SCOPE_FRONTEND, $index->getDefault('_scope'));
    }

    public function testReturnsTheDefaultController(): void
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $frontend = $collection->get('contao_frontend');

        $this->assertNotNull($frontend);

        $this->assertSame(
            'Contao\CoreBundle\Controller\FrontendController::indexAction',
            $frontend->getDefault('_controller')
        );

        $index = $collection->get('contao_index');

        $this->assertNotNull($index);

        $this->assertSame(
            'Contao\CoreBundle\Controller\FrontendController::indexAction',
            $index->getDefault('_controller')
        );
    }

    public function testFailsToGenerateTheFrontEndUrlIfTheAliasIsMissing(): void
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->expectException(MissingMandatoryParametersException::class);

        $router->generate('contao_frontend');
    }

    public function testGeneratesTheFrontEndUrlWithoutLocale(): void
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertSame('/foobar.html', $router->generate('contao_frontend', ['alias' => 'foobar']));
    }

    public function testGeneratesTheFrontEndUrlWithLocale(): void
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertSame(
            '/en/foobar.html',
            $router->generate('contao_frontend', ['alias' => 'foobar', '_locale' => 'en'])
        );
    }

    public function testAddsTheUrlSuffix(): void
    {
        $loader = new FrontendLoader(true, '.xhtml');
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertSame(
            '/en/foobar.xhtml',
            $router->generate('contao_frontend', ['alias' => 'foobar', '_locale' => 'en'])
        );
    }

    public function testFailsToGenerateTheFrontEndUrlIfTheLocaleIsMissing(): void
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->expectException(MissingMandatoryParametersException::class);

        $router->generate('contao_frontend', ['alias' => 'foobar']);
    }

    public function testGeneratesTheIndexUrlWithoutLocale(): void
    {
        $loader = new FrontendLoader(false);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertSame('/', $router->generate('contao_index'));
    }

    public function testGeneratesTheIndexUrlWithLocale(): void
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->assertSame('/en/', $router->generate('contao_index', ['_locale' => 'en']));
    }

    public function testFailsToGenerateTheIndexUrlIfTheLocaleIsMissing(): void
    {
        $loader = new FrontendLoader(true);
        $collection = $loader->load('.', 'bundles');
        $router = $this->getRouter($collection);

        $this->expectException(MissingMandatoryParametersException::class);

        $router->generate('contao_index');
    }

    private function getRouter(RouteCollection $collection): Router
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->method('load')
            ->willReturn($collection)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('routing.loader', $loader);

        return new Router($container, '');
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FrontendLoader extends Loader
{
    private bool $prependLocale;
    private string $urlSuffix;

    /**
     * @internal Do not inherit from this class; decorate the "contao.routing.frontend_loader" service instead
     */
    public function __construct(bool $prependLocale, string $urlSuffix = '.html')
    {
        trigger_deprecation('contao/core-bundle', '4.10', 'Using the "Contao\CoreBundle\Routing\FrontendLoader" class has been deprecated and will no longer work in Contao 5.0. Use Symfony routing instead.');

        $this->prependLocale = $prependLocale;
        $this->urlSuffix = $urlSuffix;
    }

    public function load($resource, string $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        $defaults = [
            '_token_check' => true,
            '_controller' => 'Contao\CoreBundle\Controller\FrontendController::indexAction',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
        ];

        $this->addFrontendRoute($routes, $defaults);
        $this->addIndexRoute($routes, $defaults);

        return $routes;
    }

    public function supports($resource, string $type = null): bool
    {
        return 'contao_frontend' === $type;
    }

    /**
     * Adds the frontend route, which is language-aware.
     */
    private function addFrontendRoute(RouteCollection $routes, array $defaults): void
    {
        $route = new Route('/{alias}'.$this->urlSuffix, $defaults, ['alias' => '.+']);

        $this->addLocaleToRoute($route);

        $routes->add('contao_frontend', $route);
    }

    /**
     * Adds a route to redirect a user to the index page.
     */
    private function addIndexRoute(RouteCollection $routes, array $defaults): void
    {
        $route = new Route('/', $defaults);

        $this->addLocaleToRoute($route);

        $routes->add('contao_index', $route);
    }

    /**
     * Adds the locale to the route if prepend_locale is enabled.
     */
    private function addLocaleToRoute(Route $route): void
    {
        if (!$this->prependLocale) {
            return;
        }

        $route->setPath('/{_locale}'.$route->getPath());
        $route->addRequirements(['_locale' => '[a-z]{2}(\-[A-Z]{2})?']);
    }
}

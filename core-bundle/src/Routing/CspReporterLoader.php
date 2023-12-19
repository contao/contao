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

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dynamically registers the CSP reporter route if enabled.
 *
 * @internal
 */
class CspReporterLoader extends Loader
{
    public function __construct(
        private readonly bool $enabled = false,
        private readonly string|null $cspReportPath = null,
    ) {
    }

    public function load(mixed $resource, string|null $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        if (!$this->enabled || !$this->cspReportPath) {
            return $routes;
        }

        $route = new Route(
            $this->cspReportPath,
            ['_controller' => 'nelmio_security.csp_reporter_controller::indexAction'],
            methods: [Request::METHOD_POST],
        );

        $routes->add('contao_csp_reporter', $route);

        return $routes;
    }

    public function supports($resource, string|null $type = null): bool
    {
        return 'contao_csp_reporter' === $type;
    }
}

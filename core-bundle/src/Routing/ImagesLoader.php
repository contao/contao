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

use Contao\CoreBundle\Controller\ImagesController;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ImagesLoader extends Loader
{
    private string $pathPrefix;

    /**
     * @internal
     */
    public function __construct(string $projectDir, string $imageTargetDir)
    {
        $this->pathPrefix = Path::makeRelative($imageTargetDir, $projectDir);
    }

    public function load(mixed $resource, string $type = null): RouteCollection
    {
        $route = new Route(
            '/'.$this->pathPrefix.'/{path}',
            [
                '_controller' => ImagesController::class,
                '_bypass_maintenance' => true,
            ],
            ['path' => '.+']
        );

        $routes = new RouteCollection();
        $routes->add('contao_images', $route);

        return $routes;
    }

    public function supports($resource, string $type = null): bool
    {
        return 'contao_images' === $type;
    }
}

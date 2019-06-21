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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ImagesLoader extends Loader
{
    /**
     * @var string
     */
    private $pathPrefix;

    public function __construct(string $projectDir, string $imageTargetDir, Filesystem $filesystem)
    {
        $this->pathPrefix = rtrim($filesystem->makePathRelative($imageTargetDir, $projectDir), '/');
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        $route = new Route(
            '/'.$this->pathPrefix.'/{path}',
            ['_controller' => 'contao.controller.images'],
            ['path' => '.+']
        );

        $routes = new RouteCollection();
        $routes->add('contao_images', $route);

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return 'contao_images' === $type;
    }
}

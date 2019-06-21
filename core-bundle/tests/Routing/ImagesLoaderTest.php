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

use Contao\CoreBundle\Routing\ImagesLoader;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ImagesLoaderTest extends TestCase
{
    public function testSupportsTheContaoImagesRoute(): void
    {
        $loader = new ImagesLoader(
            $this->getFixturesDir(),
            $this->getFixturesDir().'/path/to/images',
            new Filesystem()
        );

        $this->assertTrue($loader->supports('.', 'contao_images'));
    }

    public function testUsesTheCorrectPath(): void
    {
        $loader = new ImagesLoader(
            $this->getFixturesDir(),
            $this->getFixturesDir().'/path/to/images',
            new Filesystem()
        );

        $route = $loader->load('.', 'contao_images')->get('contao_images');

        $this->assertNotNull($route);
        $this->assertSame('/path/to/images/{path}', $route->getPath());
        $this->assertSame('contao.controller.images', $route->getDefault('_controller'));
        $this->assertSame('.+', $route->getRequirement('path'));
    }
}

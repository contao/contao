<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Studio;

use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\LightBoxResult;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class StudioTest extends TestCase
{
    // todo: How do you really test factory methods ...?

    public function testCreateFigureBuilder(): void
    {
        $studio = $this->getStudio();

        $figureBuilder = $studio->createFigureBuilder();
        $this->assertInstanceOf(FigureBuilder::class, $figureBuilder);
    }

    public function testCreateImage(): void
    {
        $studio = $this->getStudio();

        $imagResult = $studio->createImage('path/to/file.png', [100, 200, 'crop']);
        $this->assertInstanceOf(ImageResult::class, $imagResult);
    }

    public function testCreateLightBoxImage(): void
    {
        $studio = $this->getStudio();

        $lightBoxResult = $studio->createLightBoxImage(null, 'foo://bar', [100, 200, 'crop'], '12345');
        $this->assertInstanceOf(LightBoxResult::class, $lightBoxResult);
    }

    private function getStudio(): Studio
    {
        /** @var ContainerInterface&MockObject $locator */
        $locator = $this->createMock(ContainerInterface::class);

        return new Studio($locator);
    }
}

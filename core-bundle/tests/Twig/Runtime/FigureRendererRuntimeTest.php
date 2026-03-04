<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\FigureRuntime;

class FigureRendererRuntimeTest extends TestCase
{
    public function testDelegatesCallsWhenBuildingFigure(): void
    {
        $figure = new Figure($this->createStub(ImageResult::class));

        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('buildFigure')
            ->with('123', '_my_size', ['foo' => 'bar'])
            ->willReturn($figure)
        ;

        $this->assertSame(
            $figure,
            new FigureRuntime($figureRenderer)->buildFigure('123', '_my_size', ['foo' => 'bar']),
        );
    }
}

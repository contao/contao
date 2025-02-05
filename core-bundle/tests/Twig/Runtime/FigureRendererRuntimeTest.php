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
        $figure = new Figure($this->createMock(ImageResult::class));

        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('buildFigure')
            ->with('123', '_my_size', ['foo' => 'bar'])
            ->willReturn($figure)
        ;

        $this->assertSame(
            $figure,
            (new FigureRuntime($figureRenderer))->buildFigure('123', '_my_size', ['foo' => 'bar']),
        );
    }

    public function testDelegatesCallsWhenRenderingFigure(): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->with('123', '_my_size', ['foo' => 'bar'], 'my_template.html.twig')
            ->willReturn('<result>')
        ;

        $figureRendererRuntime = new FigureRuntime($figureRenderer);

        $this->expectUserDeprecationMessageMatches('/Using the "contao_figure" Twig function has been deprecated/');

        $result = $figureRendererRuntime->renderFigure('123', '_my_size', ['foo' => 'bar'], 'my_template.html.twig');

        $this->assertSame('<result>', $result);
    }

    public function testUsesFigureTemplateByDefaultWhenRenderingFigure(): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->with(1, null, [], '@ContaoCore/Image/Studio/figure.html.twig')
            ->willReturn('<result>')
        ;

        $this->expectUserDeprecationMessageMatches('/Using the "contao_figure" Twig function has been deprecated/');

        (new FigureRuntime($figureRenderer))->renderFigure(1, null);
    }
}

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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class FigureRendererRuntimeTest extends TestCase
{
    use ExpectDeprecationTrait;

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
            (new FigureRuntime($figureRenderer))->buildFigure('123', '_my_size', ['foo' => 'bar'])
        );
    }

    /**
     * @group legacy
     */
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

        $this->expectDeprecation('%sUsing the "contao_figure" Twig function has been deprecated%s');

        $result = $figureRendererRuntime->renderFigure('123', '_my_size', ['foo' => 'bar'], 'my_template.html.twig');

        $this->assertSame('<result>', $result);
    }

    /**
     * @group legacy
     */
    public function testUsesFigureTemplateByDefaultWhenRenderingFigure(): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->with(1, null, [], '@ContaoCore/Image/Studio/figure.html.twig')
            ->willReturn('<result>')
        ;

        $this->expectDeprecation('%sUsing the "contao_figure" Twig function has been deprecated%s');

        (new FigureRuntime($figureRenderer))->renderFigure(1, null);
    }
}

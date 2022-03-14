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

use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\FigureRendererRuntime;

class FigureRendererRuntimeTest extends TestCase
{
    public function testDelegatesCalls(): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->with('123', '_my_size', ['foo' => 'bar'], 'my_template.html.twig')
            ->willReturn('<result>')
        ;

        $figureRendererRuntime = new FigureRendererRuntime($figureRenderer);
        $result = $figureRendererRuntime->render('123', '_my_size', ['foo' => 'bar'], 'my_template.html.twig');

        $this->assertSame('<result>', $result);
    }

    public function testUsesFigureTemplateByDefault(): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->with(1, null, [], '@ContaoCore/Image/Studio/figure.html.twig')
            ->willReturn('<result>')
        ;

        (new FigureRendererRuntime($figureRenderer))->render(1, null);
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Extension;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ImageExtension;
use Contao\CoreBundle\Twig\Runtime\FigureRendererRuntime;
use Contao\CoreBundle\Twig\Runtime\PictureConfigurationRuntime;
use Twig\Node\Node;

class ImageExtensionTest extends TestCase
{
    public function testAddsTheContaoFigureAndPictureConfigurationFunctions(): void
    {
        $functions = (new ImageExtension())->getFunctions();

        $this->assertCount(2, $functions);

        [$contaoFigureFn, $pictureConfigurationFn] = $functions;

        $node = $this->createMock(Node::class);

        $this->assertSame('contao_figure', $contaoFigureFn->getName());
        $this->assertSame([FigureRendererRuntime::class, 'render'], $contaoFigureFn->getCallable());
        $this->assertSame(['html'], $contaoFigureFn->getSafe($node));

        $this->assertSame('picture_config', $pictureConfigurationFn->getName());
        $this->assertSame([PictureConfigurationRuntime::class, 'fromArray'], $pictureConfigurationFn->getCallable());
        $this->assertSame([], $pictureConfigurationFn->getSafe($node));
    }
}

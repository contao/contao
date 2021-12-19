<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Twig\Extension\RuntimeExtensionInterface;

final class FigureRendererRuntime implements RuntimeExtensionInterface
{
    private FigureRenderer $figureRenderer;

    /**
     * @internal
     */
    public function __construct(FigureRenderer $figureRenderer)
    {
        $this->figureRenderer = $figureRenderer;
    }

    /**
     * Renders a figure.
     *
     * The provided configuration array is used to configure a FigureBuilder
     * object. If not explicitly set, the default figure template will be used
     * to render the results.
     *
     * Returns null if the resource is invalid.
     *
     * @param int|string|FilesModel|ImageInterface       $from          Can be a FilesModel, an ImageInterface, a tl_files UUID/ID/path or a file system path
     * @param int|string|array|PictureConfiguration|null $size          A picture size configuration or reference
     * @param array<string, mixed>                       $configuration Configuration for the FigureBuilder
     */
    public function render($from, $size, array $configuration = [], string $template = '@ContaoCore/Image/Studio/figure.html.twig'): ?string
    {
        return $this->figureRenderer->render($from, $size, $configuration, $template);
    }
}

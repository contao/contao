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

use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Twig\Extension\RuntimeExtensionInterface;

final class FigureRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly FigureRenderer $figureRenderer)
    {
    }

    /**
     * Renders a figure.
     *
     * The provided configuration array is used to configure a FigureBuilder object. If not
     * explicitly set, the default figure template will be used to render the results.
     *
     * Returns null if the resource is invalid.
     *
     * @param int|string|FilesModel|ImageInterface       $from          Can be a FilesModel, an ImageInterface, a tl_files UUID/ID/path or a file system path
     * @param int|string|array|PictureConfiguration|null $size          A picture size configuration or reference
     * @param array<string, mixed>                       $configuration Configuration for the FigureBuilder
     *
     * @deprecated Deprecated since Contao 5.0, to be removed in Contao 6.
     */
    public function renderFigure(FilesModel|ImageInterface|int|string $from, PictureConfiguration|array|int|string|null $size, array $configuration = [], string $template = '@ContaoCore/Image/Studio/figure.html.twig'): string|null
    {
        trigger_deprecation('contao/core-bundle', '5.0', 'Using the "contao_figure" Twig function has been deprecated and will no longer work in Contao 6. Use the "figure" Twig function instead.');

        return $this->figureRenderer->render($from, $size, $configuration, $template);
    }

    /**
     * Builds a figure.
     *
     * The provided configuration array is used to configure a FigureBuilder object.
     *
     * Returns null if the resource is invalid.
     *
     * @param int|string|FilesModel|ImageInterface       $from          Can be a FilesModel, an ImageInterface, a tl_files UUID/ID/path or a file system path
     * @param int|string|array|PictureConfiguration|null $size          A picture size configuration or reference
     * @param array<string, mixed>                       $configuration Configuration for the FigureBuilder
     */
    public function buildFigure(FilesModel|ImageInterface|int|string $from, PictureConfiguration|array|int|string|null $size, array $configuration = []): Figure|null
    {
        return $this->figureRenderer->buildFigure($from, $size, $configuration);
    }
}

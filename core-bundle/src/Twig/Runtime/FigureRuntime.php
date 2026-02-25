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

use Contao\CoreBundle\Filesystem\FilesystemItem;
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
     * Builds a figure.
     *
     * The provided configuration array is used to configure a FigureBuilder object.
     *
     * Returns null if the resource is invalid.
     *
     * @param int|string|FilesModel|FilesystemItem|ImageInterface $from          Can be a FilesModel, a FilesystemItem, an ImageInterface, a tl_files UUID/ID/path or a file system path
     * @param int|string|array|PictureConfiguration|null          $size          A picture size configuration or reference
     * @param array<string, mixed>                                $configuration Configuration for the FigureBuilder
     */
    public function buildFigure(FilesModel|FilesystemItem|ImageInterface|int|string $from, PictureConfiguration|array|int|string|null $size, array $configuration = []): Figure|null
    {
        return $this->figureRenderer->buildFigure($from, $size, $configuration);
    }
}

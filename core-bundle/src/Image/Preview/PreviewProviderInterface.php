<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Preview;

use Contao\Image\ImageDimensions;

interface PreviewProviderInterface
{
    public function supports(string $path, string $fileHeader = ''): bool;

    /**
     * @param int $size Minimal size the preview image should have in each
     *                  dimension. Can be larger for implementation reasons or
     *                  smaller if there is not enough data.
     */
    public function generatePreview(string $sourcePath, int $size, string $targetPath): void;

    /**
     * Calculate the exact dimensions the preview image will have when generated.
     *
     * @param int    $size       See self::generatePreview()
     * @param string $fileHeader First X bytes of the source file
     */
    public function getDimensions(string $path, int $size = 0, string $fileHeader = ''): ImageDimensions;

    /**
     * Lowercase image format file extension (e.g. jpg, png or gif) the preview
     * image will have when generated.
     *
     * @param int    $size       See self::generatePreview()
     * @param string $fileHeader First X bytes of the source file
     */
    public function getImageFormat(string $path, int $size = 0, string $fileHeader = ''): string;
}

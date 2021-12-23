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

interface PreviewProviderInterface
{
    public function getFileHeaderSize(): int;

    public function supports(string $path, string $fileHeader = ''): bool;

    /**
     * @param int    $size       Minimal size the preview image should have in
     *                           each dimension. Can be larger for
     *                           implementation reasons or smaller if there is
     *                           not enough data.
     * @param string $targetPath Target path without file extension
     *
     * @throws UnableToGeneratePreviewException
     *
     * @return string Target path including the file extension
     */
    public function generatePreview(string $sourcePath, int $size, string $targetPath, array $options = []): string;
}

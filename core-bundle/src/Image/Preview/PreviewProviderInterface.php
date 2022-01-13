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

    public function supports(string $path, string $fileHeader = '', array $options = []): bool;

    /**
     * @param int      $size               Minimal size the preview image should
     *                                     have in each dimension. Can be larger
     *                                     for implementation reasons or smaller
     *                                     if there is not enough data.
     * @param \Closure $targetPathCallback Returns the target path without file
     *                                     extension for the specified page
     *                                     number
     *
     * @phpstan-param \Closure(int): string $targetPathCallback
     *
     * @throws UnableToGeneratePreviewException
     *
     * @return iterable<string> Target paths including the file extension
     */
    public function generatePreviews(string $sourcePath, int $size, \Closure $targetPathCallback, int $lastPage = PHP_INT_MAX, int $firstPage = 1, array $options = []): iterable;
}

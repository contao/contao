<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\File;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Image\Exception\ExceptionInterface as ImageException;
use Contao\Image\Image;
use Contao\StringUtil;
use Contao\Validator;
use enshrined\svgSanitize\Sanitizer;
use Imagine\Exception\Exception as ImagineException;
use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
final class UploadValidator
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly bool $rejectLargeUploads,
        private readonly ImagineInterface $imagine,
    ) {
    }

    public function isValidFilename(string $filename): bool
    {
        return !str_starts_with($filename, '.') && Validator::isValidFileName($filename);
    }

    public function isAllowedFilename(string $filename): bool
    {
        $this->framework->initialize();
        $types = $this->framework->getAdapter(Config::class)->get('uploadTypes');

        return \in_array(Path::getExtension($filename, true), StringUtil::trimsplit(',', strtolower((string) $types)), true);
    }

    public function requiresImageValidation(string $filename): bool
    {
        if (!$this->rejectLargeUploads || !\in_array(Path::getExtension($filename, true), ['gif', 'jpg', 'jpeg', 'png', 'webp', 'avif', 'heic', 'jxl'], true)) {
            return false;
        }

        $this->framework->initialize();
        $config = $this->framework->getAdapter(Config::class);

        return $config->get('imageWidth') && $config->get('imageHeight');
    }

    public function validateImage(string $localPath): void
    {
        $this->framework->initialize();

        try {
            $dimensions = new Image($localPath, $this->imagine)->getDimensions()->getSize();
        } catch (ImageException|ImagineException $exception) {
            throw new InvalidImageException('The image dimensions could not be determined.', 0, $exception);
        }

        $config = $this->framework->getAdapter(Config::class);

        if ($dimensions->getWidth() > $config->get('imageWidth') || $dimensions->getHeight() > $config->get('imageHeight')) {
            throw new ImageTooLargeException('The image exceeds the configured maximum dimensions.');
        }
    }

    public function sanitizeSvg(string $contents): string|null
    {
        $compressed = str_starts_with($contents, "\x1f\x8b");

        if ($compressed) {
            $contents = @gzdecode($contents);
        }

        if (!$contents) {
            return null;
        }

        $contents = new Sanitizer()->sanitize($contents);

        if (!$contents) {
            return null;
        }

        return $compressed ? gzencode($contents) : $contents;
    }
}

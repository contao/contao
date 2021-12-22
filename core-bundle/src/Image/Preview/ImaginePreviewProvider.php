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
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Imagick\Imagine as ImagickImagine;

class ImaginePreviewProvider implements PreviewProviderInterface
{
    private ImagineInterface $imagine;

    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    public function getFileHeaderSize(): int
    {
        return 0;
    }

    public function supports(string $path, string $fileHeader = ''): bool
    {
        $format = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $this->imagineSupportsFormat($format);
    }

    public function generatePreview(string $sourcePath, int $size, string $targetPath, array $options = []): void
    {
        try {
            $image = $this->imagine->open($sourcePath)->layers()->get(0);
            $targetSize = $this->getDimensionsFromImageSize($image->getSize(), $size)->getSize();

            $image
                ->resize($targetSize)
                ->save($targetPath, ['format' => 'png'])
            ;
        } catch (\Throwable $exception) {
            throw new UnableToGeneratePreviewException('', 0, $exception);
        }
    }

    public function getDimensions(string $path, int $size = 0, string $fileHeader = '', array $options = []): ImageDimensions
    {
        $imageSize = null;

        // Try to get the size from the file header to increase performance
        if ('' !== $fileHeader) {
            try {
                $imageSize = $this->imagine->load($fileHeader)->getSize();
            } catch (\Throwable $exception) {
                // Unable to get the size from the file header, need to load
                // the whole file instead
            }
        }

        if (null === $imageSize) {
            $imageSize = $this->imagine->open($path)->getSize();
        }

        return $this->getDimensionsFromImageSize($imageSize, $size);
    }

    public function getImageFormat(string $path, int $size = 0, string $fileHeader = '', array $options = []): string
    {
        return 'png';
    }

    private function getDimensionsFromImageSize(BoxInterface $imageSize, int $size): ImageDimensions
    {
        $width = $imageSize->getWidth();
        $height = $imageSize->getHeight();
        $scaleFactor = 0 === $size ? 1 : min(1, $size / min($width, $height));

        return new ImageDimensions(
            new Box(
                (int) round($width * $scaleFactor),
                (int) round($height * $scaleFactor)
            )
        );
    }

    private function imagineSupportsFormat(string $format): bool
    {
        // TODO: Use once Imagine 1.3.0 was released
        //if ($this->imagine instanceof InfoProvider)
        //{
        //    return $this->imagine->getDriverInfo()->isFormatSupported($format);
        //}

        if ($this->imagine instanceof ImagickImagine) {
            /** @psalm-suppress UndefinedClass */
            return \in_array(strtoupper($format), \Imagick::queryFormats(strtoupper($format)), true);
        }

        if ($this->imagine instanceof GmagickImagine) {
            /** @psalm-suppress UndefinedClass */
            return \in_array(strtoupper($format), (new \Gmagick())->queryformats(strtoupper($format)), true);
        }

        if ($this->imagine instanceof GdImagine) {
            return \function_exists('image'.$format);
        }

        throw new \RuntimeException(sprintf('Unsupported Imagine implementation "%s"', \get_class($this->imagine)));
    }
}

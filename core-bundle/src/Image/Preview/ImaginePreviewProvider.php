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
use Imagine\Factory\ClassFactoryInterface;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\CMYK;
use Imagine\Image\Palette\Grayscale;
use Imagine\Image\Palette\RGB;
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

    public function generatePreview(string $sourcePath, int $size, string $targetPath, array $options = []): string
    {
        $targetPath = "$targetPath.png";

        try {
            if ($this->imagine instanceof ImagickImagine && 'pdf' === strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION))) {
                $image = $this->openImagickPdf($sourcePath);
            } else {
                $image = $this->imagine->open($sourcePath)->layers()->get(0);
            }

            $targetSize = $this->getDimensionsFromImageSize($image->getSize(), $size)->getSize();

            $image
                ->resize($targetSize)
                ->save($targetPath, ['format' => 'png'])
            ;
        } catch (\Throwable $exception) {
            throw new UnableToGeneratePreviewException('', 0, $exception);
        }

        return $targetPath;
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

    private function openImagickPdf(string $sourcePath): ImageInterface
    {
        $imagick = new \Imagick();
        $imagick->setResolution(144, 144);
        $imagick->readImage($sourcePath.'[0]');

        $palette = new RGB();

        if (\Imagick::COLORSPACE_CMYK === $imagick->getImageColorspace()) {
            $palette = new CMYK();
        } elseif (\Imagick::COLORSPACE_GRAY === $imagick->getImageColorspace()) {
            $palette = new Grayscale();
        }

        return $this->imagine->getClassFactory()->createImage(
            ClassFactoryInterface::HANDLE_IMAGICK,
            $imagick,
            $palette,
            $this->imagine->getMetadataReader()->readFile($sourcePath),
        );
    }
}

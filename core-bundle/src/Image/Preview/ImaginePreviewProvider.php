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
    public function __construct(private ImagineInterface $imagine)
    {
    }

    public function getFileHeaderSize(): int
    {
        return 0;
    }

    public function supports(string $path, string $fileHeader = '', array $options = []): bool
    {
        $format = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $this->imagineSupportsFormat($format);
    }

    public function generatePreviews(string $sourcePath, int $size, \Closure $targetPathCallback, int $lastPage = PHP_INT_MAX, int $firstPage = 1, array $options = []): iterable
    {
        try {
            $images = [];

            if ($this->imagine instanceof ImagickImagine) {
                $images = iterator_to_array($this->openImagick($sourcePath, $size, $firstPage, $lastPage)->layers(), false);
            } elseif ($this->imagine instanceof GmagickImagine) {
                $images = iterator_to_array($this->openGmagick($sourcePath, $size, $firstPage, $lastPage)->layers(), false);
            } else {
                $layers = $this->imagine->open($sourcePath)->layers();

                for ($page = $firstPage; $page <= $lastPage; ++$page) {
                    if (!$layers->has($page - 1)) {
                        break;
                    }

                    $images[] = $layers->get($page - 1);
                }
            }

            $targetPaths = [];
            $page = $firstPage;

            foreach ($images as $image) {
                $image
                    ->resize($this->getDimensionsFromImageSize($image->getSize(), $size)->getSize())
                    ->save($targetPaths[] = $targetPathCallback($page).'.png', ['format' => 'png'])
                ;

                if (++$page > $lastPage) {
                    break;
                }
            }
        } catch (\Throwable $exception) {
            throw new UnableToGeneratePreviewException('', 0, $exception);
        }

        return $targetPaths;
    }

    private function getDimensionsFromImageSize(BoxInterface $imageSize, int $size): ImageDimensions
    {
        $width = $imageSize->getWidth();
        $height = $imageSize->getHeight();
        $scaleFactor = min(1, $size / min($width, $height));

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
        // if ($this->imagine instanceof InfoProvider) {
        //    return $this->imagine->getDriverInfo()->isFormatSupported($format);
        // }

        if ($this->imagine instanceof ImagickImagine) {
            return \in_array(strtoupper($format), \Imagick::queryFormats(strtoupper($format)), true);
        }

        if ($this->imagine instanceof GmagickImagine) {
            return \in_array(strtoupper($format), (new \Gmagick())->queryformats(strtoupper($format)), true);
        }

        if ($this->imagine instanceof GdImagine) {
            return \function_exists('image'.$format);
        }

        throw new \RuntimeException(sprintf('Unsupported Imagine implementation "%s"', $this->imagine::class));
    }

    private function openImagick(string $sourcePath, int $size, int $firstPage, int $lastPage): ImageInterface
    {
        return $this->openMagick(\Imagick::class, $sourcePath, $size, $firstPage, $lastPage);
    }

    private function openGmagick(string $sourcePath, int $size, int $firstPage, int $lastPage): ImageInterface
    {
        return $this->openMagick(\Gmagick::class, $sourcePath, $size, $firstPage, $lastPage);
    }

    /**
     * @param class-string<\Gmagick|\Imagick> $magickClass
     */
    private function openMagick(string $magickClass, string $sourcePath, int $size, int $firstPage, int $lastPage): ImageInterface
    {
        if (PHP_INT_MAX === $lastPage) {
            $lastPage = 0x7FFFFF; // 32bit PDF limit
        }

        $pagedPath = $sourcePath.'['.($firstPage - 1).'-'.($lastPage - 1).']';
        $magick = new $magickClass();

        if (\is_callable([$magick, 'setResolution'])) {
            $resolution = 72;
            $magick->setResolution($resolution, $resolution);

            if (
                \is_callable([$magick, 'pingImage'])
                && 'pdf' === strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION))
            ) {
                $magick->pingImage($pagedPath);

                // Set the resolution so that the PDF gets rendered in the requested size
                if ($magick->getImageWidth() > 0 && $magick->getImageHeight() > 0) {
                    $resolution = $size * $resolution / min($magick->getImageWidth(), $magick->getImageHeight());
                }

                $magick->clear();

                $magick = new $magickClass();
                $magick->setResolution($resolution, $resolution);
            }
        }

        $magick->readImage($pagedPath);

        if (
            \is_callable([$magick, 'setImageAlphaChannel'])
            && \defined("$magickClass::ALPHACHANNEL_REMOVE")
            && 'pdf' === strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION))
        ) {
            // Fix white PDF background
            if (is_iterable($magick)) {
                foreach ($magick as $magickPage) {
                    $magickPage->setImageAlphaChannel($magickClass::ALPHACHANNEL_REMOVE);
                }
            } else {
                $magick->setImageAlphaChannel($magickClass::ALPHACHANNEL_REMOVE);
            }
        }

        $palette = new RGB();

        if ($magickClass::COLORSPACE_CMYK === $magick->getImageColorspace()) {
            $palette = new CMYK();
        } elseif ($magickClass::COLORSPACE_GRAY === $magick->getImageColorspace()) {
            $palette = new Grayscale();
        }

        return $this->imagine->getClassFactory()->createImage(
            \constant(ClassFactoryInterface::class.'::HANDLE_'.strtoupper($magickClass)),
            $magick,
            $palette,
            $this->imagine->getMetadataReader()->readFile($sourcePath),
        );
    }
}

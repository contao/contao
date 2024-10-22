<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image;

use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizer;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Path;

class Resizer extends DeferredResizer
{
    public function resize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        try {
            return parent::resize($image, $config, $options);
        } catch (ImagineRuntimeException $exception) {
            throw $this->enhanceImagineException($exception, $image);
        }
    }

    public function resizeDeferredImage(DeferredImageInterface $image, bool $blocking = true): ImageInterface|null
    {
        try {
            return parent::resizeDeferredImage($image, $blocking);
        } catch (ImagineRuntimeException $exception) {
            throw $this->enhanceImagineException($exception, $image);
        }
    }

    private function enhanceImagineException(ImagineRuntimeException $exception, ImageInterface $image): ImagineRuntimeException|\RuntimeException
    {
        $format = Path::getExtension($image->getPath(), true);

        if (!$this->formatIsSupported($format, $image->getImagine())) {
            return new \RuntimeException(\sprintf('Image format "%s" is not supported in %s on this environment. Consider removing this format from contao.image.valid_extensions or switch the contao.image.imagine_service to an implementation that supports it.', $format, $image->getImagine()::class), $exception->getCode(), $exception);
        }

        return $exception;
    }

    private function formatIsSupported(string $format, ImagineInterface $imagine): bool
    {
        if ('' === $format) {
            return false;
        }

        try {
            $imagine->create(new Box(1, 1))->get($format);
        } catch (\Throwable) {
            return false;
        }

        return true;
    }
}

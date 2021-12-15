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

use Contao\Config;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\File;
use Contao\Image as LegacyImage;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizer as ImageResizer;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeCoordinates;
use Contao\Image\ResizeOptions;
use Contao\System;
use Imagine\Exception\RuntimeException as ImagineRuntimeException;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Path;

/**
 * Resizes image objects and executes the legacy hooks.
 */
class LegacyResizer extends ImageResizer implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    private ?LegacyImage $legacyImage = null;

    public function resize(ImageInterface $image, ResizeConfiguration $config, ResizeOptions $options): ImageInterface
    {
        $this->framework->initialize(true);

        $projectDir = (string) System::getContainer()->getParameter('kernel.project_dir');

        if ($this->hasExecuteResizeHook() || $this->hasGetImageHook()) {
            trigger_deprecation('contao/core-bundle', '4.0', 'Using the "executeResize" and "getImage" hooks has been deprecated and will no longer work in Contao 5.0. Replace the "contao.image.legacy_resizer" service instead.');

            $this->legacyImage = null;
            $legacyPath = $image->getPath();

            if (Path::isBasePath($projectDir, $legacyPath)) {
                $legacyPath = Path::makeRelative($legacyPath, $projectDir);
                $this->legacyImage = new LegacyImage(new File($legacyPath));
                $this->legacyImage->setTargetWidth($config->getWidth());
                $this->legacyImage->setTargetHeight($config->getHeight());
                $this->legacyImage->setResizeMode($config->getMode());
                $this->legacyImage->setZoomLevel($config->getZoomLevel());

                if (($targetPath = $options->getTargetPath()) && Path::isBasePath($projectDir, $targetPath)) {
                    $this->legacyImage->setTargetPath(Path::makeRelative($targetPath, $projectDir));
                }

                $importantPart = $image->getImportantPart();
                $imageSize = $image->getDimensions()->getSize();

                $this->legacyImage->setImportantPart([
                    'x' => $importantPart->getX() * $imageSize->getWidth(),
                    'y' => $importantPart->getY() * $imageSize->getHeight(),
                    'width' => $importantPart->getWidth() * $imageSize->getWidth(),
                    'height' => $importantPart->getHeight() * $imageSize->getHeight(),
                ]);
            }
        }

        if ($this->legacyImage && $this->hasExecuteResizeHook()) {
            foreach ($GLOBALS['TL_HOOKS']['executeResize'] as $callback) {
                $return = System::importStatic($callback[0])->{$callback[1]}($this->legacyImage);

                if (\is_string($return)) {
                    return $this->createImage($image, Path::join($projectDir, $return));
                }
            }
        }

        try {
            return parent::resize($image, $config, $options);
        } catch (ImagineRuntimeException $exception) {
            throw $this->enhanceImagineException($exception, $image);
        }
    }

    public function resizeDeferredImage(DeferredImageInterface $image, bool $blocking = true): ?ImageInterface
    {
        try {
            return parent::resizeDeferredImage($image, $blocking);
        } catch (ImagineRuntimeException $exception) {
            throw $this->enhanceImagineException($exception, $image);
        }
    }

    protected function executeResize(ImageInterface $image, ResizeCoordinates $coordinates, string $path, ResizeOptions $options): ImageInterface
    {
        if ($this->legacyImage && $this->hasGetImageHook()) {
            $projectDir = System::getContainer()->getParameter('kernel.project_dir');

            foreach ($GLOBALS['TL_HOOKS']['getImage'] as $callback) {
                $return = System::importStatic($callback[0])->{$callback[1]}(
                    $this->legacyImage->getOriginalPath(),
                    $this->legacyImage->getTargetWidth(),
                    $this->legacyImage->getTargetHeight(),
                    $this->legacyImage->getResizeMode(),
                    $this->legacyImage->getCacheName(),
                    new File($this->legacyImage->getOriginalPath()),
                    $this->legacyImage->getTargetPath(),
                    $this->legacyImage
                );

                if (\is_string($return)) {
                    return $this->createImage($image, Path::join($projectDir, $return));
                }
            }
        }

        if ($image->getImagine() instanceof GdImagine) {
            $dimensions = $image->getDimensions();

            $config = $this->framework->getAdapter(Config::class);
            $gdMaxImgWidth = $config->get('gdMaxImgWidth');
            $gdMaxImgHeight = $config->get('gdMaxImgHeight');

            // Return the path to the original image if it cannot be handled
            if (
                $dimensions->getSize()->getWidth() > $gdMaxImgWidth
                || $dimensions->getSize()->getHeight() > $gdMaxImgHeight
                || $coordinates->getSize()->getWidth() > $gdMaxImgWidth
                || $coordinates->getSize()->getHeight() > $gdMaxImgHeight
            ) {
                return $this->createImage($image, $image->getPath());
            }
        }

        return parent::executeResize($image, $coordinates, $path, $options);
    }

    private function hasExecuteResizeHook(): bool
    {
        return !empty($GLOBALS['TL_HOOKS']['executeResize']) && \is_array($GLOBALS['TL_HOOKS']['executeResize']);
    }

    private function hasGetImageHook(): bool
    {
        return !empty($GLOBALS['TL_HOOKS']['getImage']) && \is_array($GLOBALS['TL_HOOKS']['getImage']);
    }

    private function enhanceImagineException(ImagineRuntimeException $exception, ImageInterface $image): \Throwable
    {
        $format = Path::getExtension($image->getPath(), true);

        if (!$this->formatIsSupported($format, $image->getImagine())) {
            return new \RuntimeException(sprintf('Image format "%s" is not supported in %s on this environment. Consider removing this format from contao.image.valid_extensions or switch the contao.image.imagine_service to an implementation that supports it.', $format, \get_class($image->getImagine())), $exception->getCode(), $exception);
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
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }
}

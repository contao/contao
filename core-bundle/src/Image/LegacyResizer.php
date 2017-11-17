<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Contao\Config;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\File;
use Contao\Image as LegacyImage;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeCoordinatesInterface;
use Contao\Image\ResizeOptionsInterface;
use Contao\Image\Resizer as ImageResizer;
use Contao\System;
use Imagine\Gd\Imagine as GdImagine;

/**
 * Resizes image objects and executes the legacy hooks.
 */
class LegacyResizer extends ImageResizer implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var LegacyImage
     */
    private $legacyImage;

    /**
     * {@inheritdoc}
     */
    public function resize(ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options): ImageInterface
    {
        if ($this->hasExecuteResizeHook() || $this->hasGetImageHook()) {
            @trigger_error('Using the executeResize and getImage hooks has been deprecated and will no longer work in Contao 5.0. Replace the contao.image.resizer service instead.', E_USER_DEPRECATED);

            $this->legacyImage = null;
            $legacyPath = $image->getPath();

            if (0 === strpos($legacyPath, TL_ROOT.'/') || 0 === strpos($legacyPath, TL_ROOT.'\\')) {
                $legacyPath = substr($legacyPath, \strlen(TL_ROOT) + 1);
                $this->legacyImage = new LegacyImage(new File($legacyPath));
                $this->legacyImage->setTargetWidth($config->getWidth());
                $this->legacyImage->setTargetHeight($config->getHeight());
                $this->legacyImage->setResizeMode($config->getMode());
                $this->legacyImage->setZoomLevel($config->getZoomLevel());

                if ($options->getTargetPath()
                    && (0 === strpos($options->getTargetPath(), TL_ROOT.'/')
                        || 0 === strpos($options->getTargetPath(), TL_ROOT.'\\')
                    )
                ) {
                    $this->legacyImage->setTargetPath(substr($options->getTargetPath(), \strlen(TL_ROOT) + 1));
                }

                $importantPart = $image->getImportantPart();

                $this->legacyImage->setImportantPart([
                    'x' => $importantPart->getPosition()->getX(),
                    'y' => $importantPart->getPosition()->getY(),
                    'width' => $importantPart->getSize()->getWidth(),
                    'height' => $importantPart->getSize()->getHeight(),
                ]);
            }
        }

        if ($this->legacyImage && $this->hasExecuteResizeHook()) {
            foreach ($GLOBALS['TL_HOOKS']['executeResize'] as $callback) {
                $return = System::importStatic($callback[0])->{$callback[1]}($this->legacyImage);

                if (\is_string($return)) {
                    return $this->createImage($image, TL_ROOT.'/'.$return);
                }
            }
        }

        return parent::resize($image, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeResize(ImageInterface $image, ResizeCoordinatesInterface $coordinates, $path, ResizeOptionsInterface $options): ImageInterface
    {
        if ($this->legacyImage && $this->hasGetImageHook()) {
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
                    return $this->createImage($image, TL_ROOT.'/'.$return);
                }
            }
        }

        if ($image->getImagine() instanceof GdImagine) {
            $dimensions = $image->getDimensions();

            /** @var Config $config */
            $config = $this->framework->getAdapter(Config::class);
            $gdMaxImgWidth = $config->get('gdMaxImgWidth');
            $gdMaxImgHeight = $config->get('gdMaxImgHeight');

            // Return the path to the original image if it cannot be handled
            if ($dimensions->getSize()->getWidth() > $gdMaxImgWidth
                || $dimensions->getSize()->getHeight() > $gdMaxImgHeight
                || $coordinates->getSize()->getWidth() > $gdMaxImgWidth
                || $coordinates->getSize()->getHeight() > $gdMaxImgHeight
            ) {
                return $this->createImage($image, $image->getPath());
            }
        }

        return parent::executeResize($image, $coordinates, $path, $options);
    }

    /**
     * Checks if the executeResize hook is set.
     *
     * @return bool
     */
    private function hasExecuteResizeHook(): bool
    {
        return !empty($GLOBALS['TL_HOOKS']['executeResize']) && \is_array($GLOBALS['TL_HOOKS']['executeResize']);
    }

    /**
     * Checks if the getImage hook is set.
     *
     * @return bool
     */
    private function hasGetImageHook(): bool
    {
        return !empty($GLOBALS['TL_HOOKS']['getImage']) && \is_array($GLOBALS['TL_HOOKS']['getImage']);
    }
}

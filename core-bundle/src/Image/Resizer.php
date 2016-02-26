<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImagineInterface;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image as LegacyImage;
use Contao\File;
use Contao\System;
use Contao\Image\Resizer as ImageResizer;
use Contao\Image\Image;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeCoordinates;
use Contao\Image\ImportantPart;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Resizes Image objects via Contao\Image\Resizer and executes legacy hooks.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Resizer extends ImageResizer
{
    /**
     * @var LegacyImage
     */
    private $legacyImage;

    /**
     * {@inheritdoc}
     */
    public function resize(Image $image, ResizeConfiguration $resizeConfig, array $imagineOptions = [], $targetPath = null, $bypassCache = false)
    {
        if ((
            isset($GLOBALS['TL_HOOKS']['executeResize']) &&
            is_array($GLOBALS['TL_HOOKS']['executeResize']) &&
            count($GLOBALS['TL_HOOKS']['executeResize'])
        ) || (
            isset($GLOBALS['TL_HOOKS']['getImage']) &&
            is_array($GLOBALS['TL_HOOKS']['getImage']) &&
            count($GLOBALS['TL_HOOKS']['getImage'])
        )) {

            @trigger_error(
                'Using the executeResize and getImage hooks has been deprecated and will no longer work in Contao 5.0. '
                    . 'Use the resize service instead.',
                E_USER_DEPRECATED
            );

            $this->legacyImage = null;
            $legacyPath = $image->getPath();

            if (strpos($legacyPath, TL_ROOT . '/') === 0 || strpos($legacyPath, TL_ROOT . '\\') === 0) {
                $legacyPath = substr($legacyPath, strlen(TL_ROOT) + 1);
                $this->legacyImage = new LegacyImage(new File($legacyPath));
                $this->legacyImage->setTargetWidth($resizeConfig->getWidth());
                $this->legacyImage->setTargetHeight($resizeConfig->getHeight());
                $this->legacyImage->setResizeMode($resizeConfig->getMode());
                $this->legacyImage->setZoomLevel($resizeConfig->getZoomLevel());
                $this->legacyImage->setTargetPath($targetPath);
                $importantPart = $image->getImportantPart();
                $this->legacyImage->setImportantPart([
                    'x' => $importantPart->getPosition()->getX(),
                    'y' => $importantPart->getPosition()->getY(),
                    'width' => $importantPart->getSize()->getWidth(),
                    'height' => $importantPart->getSize()->getHeight(),
                ]);
            }

        }

        if (isset($GLOBALS['TL_HOOKS']['executeResize']) && is_array($GLOBALS['TL_HOOKS']['executeResize']) && $this->legacyImage) {
            foreach ($GLOBALS['TL_HOOKS']['executeResize'] as $callback) {
                $return = System::importStatic($callback[0])->{$callback[1]}($this->legacyImage);
                if (is_string($return)) {
                    return $this->createImage($image, TL_ROOT . '/' . $return);
                }
            }
        }

        return parent::resize($image, $resizeConfig, $imagineOptions, $targetPath, $bypassCache);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeResize(Image $image, ResizeCoordinates $coordinates, $path, array $imagineOptions)
    {
        if (isset($GLOBALS['TL_HOOKS']['getImage']) && is_array($GLOBALS['TL_HOOKS']['getImage']) && $this->legacyImage) {
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
                if (is_string($return)) {
                    return $this->createImage($image, TL_ROOT . '/' . $return);
                }
            }
        }

        return parent::executeResize($image, $coordinates, $path, $imagineOptions);
    }
}

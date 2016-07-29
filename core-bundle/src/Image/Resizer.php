<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Imagine\Gd\Imagine as GdImagine;
use Contao\Image as LegacyImage;
use Contao\File;
use Contao\System;
use Contao\Image\Resizer as ImageResizer;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeCoordinatesInterface;
use Contao\Image\ResizeOptionsInterface;

/**
 * Resizes Image objects via Contao\Image\Resizer and executes legacy hooks.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class Resizer extends ImageResizer
{
    use FrameworkAwareTrait;

    /**
     * @var LegacyImage
     */
    private $legacyImage;

    /**
     * {@inheritdoc}
     */
    public function resize(ImageInterface $image, ResizeConfigurationInterface $config, ResizeOptionsInterface $options)
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
                    .'Use the resize service instead.',
                E_USER_DEPRECATED
            );

            $this->legacyImage = null;
            $legacyPath = $image->getPath();

            if (0 === strpos($legacyPath, TL_ROOT.'/') || 0 === strpos($legacyPath, TL_ROOT.'\\')) {
                $legacyPath = substr($legacyPath, strlen(TL_ROOT) + 1);
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
                    $this->legacyImage->setTargetPath(substr($options->getTargetPath(), strlen(TL_ROOT) + 1));
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

        if (isset($GLOBALS['TL_HOOKS']['executeResize'])
            && is_array($GLOBALS['TL_HOOKS']['executeResize'])
            && $this->legacyImage
        ) {
            foreach ($GLOBALS['TL_HOOKS']['executeResize'] as $callback) {
                $return = System::importStatic($callback[0])->{$callback[1]}($this->legacyImage);

                if (is_string($return)) {
                    return $this->createImage($image, TL_ROOT.'/'.$return);
                }
            }
        }

        return parent::resize($image, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function executeResize(
        ImageInterface $image,
        ResizeCoordinatesInterface $coordinates,
        $path,
        array $imagineOptions
    ) {
        if (isset($GLOBALS['TL_HOOKS']['getImage'])
            && is_array($GLOBALS['TL_HOOKS']['getImage'])
            && $this->legacyImage
        ) {
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
                    return $this->createImage($image, TL_ROOT.'/'.$return);
                }
            }
        }

        $config = $this->framework->getAdapter('Contao\Config');

        if ($image->getImagine() instanceof GdImagine
            && ($image->getDimensions()->getSize()->getWidth() > $config->get('gdMaxImgWidth')
                || $image->getDimensions()->getSize()->getHeight() > $config->get('gdMaxImgHeight')
                || $coordinates->getSize()->getWidth() > $config->get('gdMaxImgWidth')
                || $coordinates->getSize()->getHeight() > $config->get('gdMaxImgHeight')
            )
        ) {
            return $this->createImage($image, $image->getPath());
        }

        return parent::executeResize($image, $coordinates, $path, $imagineOptions);
    }
}

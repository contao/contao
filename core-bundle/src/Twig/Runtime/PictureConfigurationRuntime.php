<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Twig\Extension\RuntimeExtensionInterface;

final class PictureConfigurationRuntime implements RuntimeExtensionInterface
{
    /**
     * Create a picture configuration from an array. This is intended to be
     * used from within templates where programmatic building isn't available.
     */
    public function fromArray(array $configArray): PictureConfiguration
    {
        $config = new PictureConfiguration();

        $config->setSize($this->createConfigItem($configArray));
        $config->setFormats($configArray['formats'] ?? []);

        $config->setSizeItems(
            array_map(
                function (array $imageSizeItem): PictureConfigurationItem {
                    return $this->createConfigItem($imageSizeItem);
                },
                $configArray['items'] ?? []
            )
        );

        return $config;
    }

    private function createConfigItem(array $imageSize): PictureConfigurationItem
    {
        $configItem = new PictureConfigurationItem();

        if (isset($imageSize['sizes'])) {
            $configItem->setSizes((string) $imageSize['sizes']);
        }

        if (isset($imageSize['densities'])) {
            $configItem->setDensities((string) $imageSize['densities']);
        }

        if (isset($imageSize['media'])) {
            $configItem->setMedia((string) $imageSize['media']);
        }

        $resizeConfig = new ResizeConfiguration();

        if (isset($imageSize['width'])) {
            $resizeConfig->setWidth((int) $imageSize['width']);
        }

        if (isset($imageSize['height'])) {
            $resizeConfig->setHeight((int) $imageSize['height']);
        }

        if (isset($imageSize['zoom'])) {
            $resizeConfig->setZoomLevel((int) $imageSize['zoom']);
        }

        if (isset($imageSize['resizeMode'])) {
            $resizeConfig->setMode((string) $imageSize['resizeMode']);
        }

        $configItem->setResizeConfig($resizeConfig);

        return $configItem;
    }
}

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

    private function createConfigItem(array $config): PictureConfigurationItem
    {
        $configItem = new PictureConfigurationItem();

        if (isset($config['sizes'])) {
            $configItem->setSizes((string) $config['sizes']);
        }

        if (isset($config['densities'])) {
            $configItem->setDensities((string) $config['densities']);
        }

        if (isset($config['media'])) {
            $configItem->setMedia((string) $config['media']);
        }

        $resizeConfig = new ResizeConfiguration();

        if (isset($config['width'])) {
            $resizeConfig->setWidth((int) $config['width']);
        }

        if (isset($config['height'])) {
            $resizeConfig->setHeight((int) $config['height']);
        }

        if (isset($config['zoom'])) {
            $resizeConfig->setZoomLevel((int) $config['zoom']);
        }

        if (isset($config['resizeMode'])) {
            $resizeConfig->setMode((string) $config['resizeMode']);
        }

        $configItem->setResizeConfig($resizeConfig);

        return $configItem;
    }
}

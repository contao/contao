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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Extension\RuntimeExtensionInterface;

final class PictureConfigurationRuntime implements RuntimeExtensionInterface
{
    private readonly PropertyAccessor $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Creates a picture configuration from an array.
     *
     * This is intended to be used from within templates where programmatic
     * building is not available.
     */
    public function fromArray(array $config): PictureConfiguration
    {
        $pictureConfiguration = new PictureConfiguration();

        // Group main configuration
        $config['size'] = $this->createPictureConfigurationItem($config);

        // Append the size item configuration keys
        $config['sizeItems'] = array_map(
            function (array $itemConfig): PictureConfigurationItem {
                $sizeItem = $this->createPictureConfigurationItem($itemConfig);

                if (!empty($itemConfig)) {
                    $this->throwInvalidArgumentException($itemConfig, 'items');
                }

                return $sizeItem;
            },
            $config['items'] ?? []
        );

        unset($config['items']);

        // Apply remaining data to root config
        $this->applyConfiguration($pictureConfiguration, $config);

        if (!empty($config)) {
            $this->throwInvalidArgumentException($config);
        }

        return $pictureConfiguration;
    }

    private function createPictureConfigurationItem(array &$config): PictureConfigurationItem
    {
        $pictureConfigurationItem = new PictureConfigurationItem();
        $resizeConfiguration = new ResizeConfiguration();

        // Transform keys for legacy reasons
        if (isset($config['zoom'])) {
            $config['zoomLevel'] = $config['zoom'];
        }

        if (isset($config['resizeMode'])) {
            $config['mode'] = $config['resizeMode'];
        }

        unset($config['zoom'], $config['resizeMode']);

        $this->applyConfiguration($pictureConfigurationItem, $config);
        $this->applyConfiguration($resizeConfiguration, $config);

        $pictureConfigurationItem->setResizeConfig($resizeConfiguration);

        return $pictureConfigurationItem;
    }

    private function applyConfiguration(object $item, array &$config): void
    {
        foreach ($config as $key => $value) {
            if (!$this->propertyAccessor->isWritable($item, $key)) {
                continue;
            }

            $this->propertyAccessor->setValue($item, $key, $value);
            unset($config[$key]);
        }
    }

    private function throwInvalidArgumentException(array $unmappedConfig, string|null $prefix = null): void
    {
        $keys = array_keys($unmappedConfig);

        // Prepend prefix
        if (null !== $prefix) {
            $keys = array_map(static fn (string $v): string => "$prefix.$v", $keys);
        }

        throw new \InvalidArgumentException(sprintf('Could not map picture configuration key(s) "%s".', implode('", "', $keys)));
    }
}

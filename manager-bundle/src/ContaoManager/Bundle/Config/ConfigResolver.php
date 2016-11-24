<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle\Config;

use Contao\ManagerBundle\ContaoManager\Dependency\DependencyResolverTrait;

/**
 * Resolves the bundles map from the configuration objects
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ConfigResolver implements ConfigResolverInterface
{
    use DependencyResolverTrait;

    /**
     * @var ConfigInterface[]
     */
    protected $configs = [];

    /**
     * @inheritdoc
     */
    public function add(ConfigInterface $config)
    {
        $this->configs[] = $config;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBundleConfigs($development)
    {
        $bundles = [];

        // Only add bundles which match the environment
        foreach ($this->configs as $config) {
            if (($development && $config->loadInDevelopment()) || (!$development && $config->loadInProduction())) {
                $bundles[$config->getName()] = $config;
            }
        }

        $loadingOrder    = $this->buildLoadingOrder();
        $replaces        = $this->buildReplaceMap();
        $normalizedOrder = $this->normalizeLoadingOrder($loadingOrder, $replaces);
        $resolvedOrder   = $this->orderByDependencies($normalizedOrder);

        return $this->order($bundles, $resolvedOrder);
    }

    /**
     * Builds the replaces from the configuration objects
     *
     * @return array
     */
    private function buildReplaceMap()
    {
        $replace = [];

        foreach ($this->configs as $bundle) {
            $name = $bundle->getName();

            foreach ($bundle->getReplace() as $package) {
                $replace[$package] = $name;
            }
        }

        return $replace;
    }

    /**
     * Builds the loading order from the configuration objects
     *
     * @return array
     */
    private function buildLoadingOrder()
    {
        // Make sure the core bundle comes first
        // TODO is this still correct?
        $loadingOrder = [
            'ContaoCoreBundle' => []
        ];

        foreach ($this->configs as $bundle) {
            $name = $bundle->getName();

            $loadingOrder[$name] = [];

            foreach ($bundle->getLoadAfter() as $package) {
                $loadingOrder[$name][] = $package;
            }
        }

        return $loadingOrder;
    }

    /**
     * Orders the bundles in a given order
     *
     * @param array $bundles
     * @param array $ordered
     *
     * @return array
     */
    private function order(array $bundles, array $ordered)
    {
        $return  = [];

        foreach ($ordered as $package) {
            if (array_key_exists($package, $bundles)) {
                $return[$package] = $bundles[$package];
            }
        }

        return $return;
    }

    /**
     * Normalizes the loading order array
     *
     * @param array $loadingOrder
     * @param array $replace
     *
     * @return array
     */
    private function normalizeLoadingOrder(array $loadingOrder, array $replace)
    {
        foreach ($loadingOrder as $bundleName => &$loadAfter) {
            if (isset($replace[$bundleName])) {
                unset($loadingOrder[$bundleName]);
            } else {
                $this->replaceBundleNames($loadAfter, $replace);
            }
        }

        return $loadingOrder;
    }

    /**
     * Replaces the legacy bundle names with their new name
     *
     * @param array $loadAfter
     * @param array $replace
     */
    private function replaceBundleNames(array &$loadAfter, array $replace)
    {
        foreach ($loadAfter as &$bundleName) {
            if (isset($replace[$bundleName])) {
                $bundleName = $replace[$bundleName];
            }
        }
    }
}

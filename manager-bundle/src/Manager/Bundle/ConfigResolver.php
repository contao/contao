<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Manager\Bundle;

use Contao\ManagerBundle\Exception\UnresolvableLoadingOrderException;

/**
 * Resolves the bundles map from the configuration objects
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ConfigResolver
{
    /**
     * @var ConfigInterface[]
     */
    protected $configs = [];

    /**
     * Adds a configuration object
     *
     * @param ConfigInterface $config
     *
     * @return $this
     */
    public function add(ConfigInterface $config)
    {
        $this->configs[] = $config;

        return $this;
    }

    /**
     * Returns a bundles map for an environment
     *
     * @param bool $development
     *
     * @return array
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
        $resolvedOrder   = $this->resolveLoadingOrder($normalizedOrder);

        return $this->order($bundles, $resolvedOrder);
    }

    /**
     * Builds the replaces from the configuration objects
     *
     * @return array
     */
    protected function buildReplaceMap()
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
    protected function buildLoadingOrder()
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
    protected function order(array $bundles, array $ordered)
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
    protected function normalizeLoadingOrder(array $loadingOrder, array $replace)
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
    protected function replaceBundleNames(array &$loadAfter, array $replace)
    {
        foreach ($loadAfter as &$bundleName) {
            if (isset($replace[$bundleName])) {
                $bundleName = $replace[$bundleName];
            }
        }
    }

    /**
     * Tries to resolve the loading order
     *
     * @param array $loadingOrder
     *
     * @return array
     *
     * @throws UnresolvableLoadingOrderException If the loading order cannot be resolved
     */
    protected function resolveLoadingOrder(array $loadingOrder)
    {
        $ordered   = [];
        $available = array_keys($loadingOrder);

        while (0 !== count($loadingOrder)) {
            $success = $this->doResolveLoadingOrder($loadingOrder, $ordered, $available);

            if (false === $success) {
                throw new UnresolvableLoadingOrderException(
                    "The bundle loading order could not be resolved.\n" . print_r($loadingOrder, true)
                );
            }
        }

        return $ordered;
    }

    /**
     * Tries to resolve the loading order
     *
     * @param array $loadingOrder
     * @param array $ordered
     * @param array $available
     *
     * @return bool True if the order could be resolved
     */
    protected function doResolveLoadingOrder(array &$loadingOrder, array &$ordered, array $available)
    {
        $failed = true;

        foreach ($loadingOrder as $name => $requires) {
            if (true === $this->canBeResolved($requires, $available, $ordered)) {
                $failed    = false;
                $ordered[] = $name;

                unset($loadingOrder[$name]);
            }
        }

        return !$failed;
    }

    /**
     * Checks whether the requirements of a bundle can be resolved
     *
     * @param array $requires
     * @param array $available
     * @param array $ordered
     *
     * @return bool True if the requirements can be resolved
     */
    protected function canBeResolved(array $requires, array $available, array $ordered)
    {
        if (0 === count($requires)) {
            return true;
        }

        return (0 === count(array_diff(array_intersect($requires, $available), $ordered)));
    }
}

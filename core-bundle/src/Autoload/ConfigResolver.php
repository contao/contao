<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Autoload;

use Contao\CoreBundle\Exception\UnresolvableLoadingOrderException;

/**
 * Resolves the bundles map from the configuration objects
 *
 * @author Leo Feyer <https://contao.org>
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
     * @param ConfigInterface $config The configuration object
     *
     * @return $this The resolver object
     */
    public function add(ConfigInterface $config)
    {
        $this->configs[] = $config;

        return $this;
    }

    /**
     * Returns a bundles map for an environment
     *
     * @param string $environment The environment
     *
     * @return array The bundles map
     */
    public function getBundlesMapForEnvironment($environment)
    {
        $bundles = [];

        // Only add bundles which match the environment
        foreach ($this->configs as $config) {
            if ($this->matchesEnvironment($config->getEnvironments(), $environment)) {
                $bundles[$config->getName()] = $config->getClass();
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
     * @return array The replaces array
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
     * @return array The loading order array
     */
    protected function buildLoadingOrder()
    {
        // Make sure the core bundle comes first
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
     * Checks whether a bundle should be loaded in an environment
     *
     * @param array  $environments The bundle environments
     * @param string $environment  The current environment
     *
     * @return bool True if the environment matches the bundle environments
     */
    protected function matchesEnvironment(array $environments, $environment)
    {
        return in_array($environment, $environments) || in_array('all', $environments);
    }

    /**
     * Orders the bundles in a given order
     *
     * @param array $bundles The bundles array
     * @param array $ordered The given order
     *
     * @return array The ordered bundles
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
     * @param array $loadingOrder The loading order array
     * @param array $replace      The replaces map
     *
     * @return array The normalized loading order array
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
     * @param array $loadAfter The load-after array
     * @param array $replace   The replaces array
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
     * @param array $loadingOrder The normalized loading order array
     *
     * @return array The resolved loading order array
     *
     * @throws UnresolvableLoadingOrderException If the loading order cannot be resolved
     */
    protected function resolveLoadingOrder(array $loadingOrder)
    {
        $ordered   = [];
        $available = array_keys($loadingOrder);

        while (!empty($loadingOrder)) {
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
     * @param array $loadingOrder The normalized loading order array
     * @param array $ordered      An array of already ordered bundles
     * @param array $available    An array of available bundles
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
     * @param array $requires  The requirements array
     * @param array $available The installed bundle names
     * @param array $ordered   The normalized order array
     *
     * @return bool True if the requirements can be resolved
     */
    protected function canBeResolved(array $requires, array $available, array $ordered)
    {
        if (empty($requires)) {
            return true;
        }

        return (0 === count(array_diff(array_intersect($requires, $available), $ordered)));
    }
}

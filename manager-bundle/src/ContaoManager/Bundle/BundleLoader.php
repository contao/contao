<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle;

use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigInterface;
use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\ParserInterface;
use Contao\ManagerBundle\ContaoManager\PluginLoader;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Finds bundles from Contao Manager plugins.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class BundleLoader
{
    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var ConfigResolverFactory
     */
    private $resolverFactory;

    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor
     *
     * @param PluginLoader          $pluginLoader
     * @param ConfigResolverFactory $resolverFactory
     * @param ParserInterface       $parser
     * @param Filesystem            $filesystem
     */
    public function __construct(
        PluginLoader $pluginLoader,
        ConfigResolverFactory $resolverFactory,
        ParserInterface $parser,
        Filesystem $filesystem = null
    ) {
        $this->pluginLoader = $pluginLoader;
        $this->resolverFactory = $resolverFactory;
        $this->parser = $parser;
        $this->filesystem = $filesystem;

        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }
    }

    /**
     * Returns an ordered bundle map
     *
     * @param bool        $development
     * @param string|null $cacheFile
     *
     * @return ConfigInterface[]
     */
    public function getBundleConfigs($development, $cacheFile = null)
    {
        if (null !== $cacheFile) {
            return $this->loadFromCache($development, $cacheFile);
        }

        return $this->loadFromPlugins($development, $cacheFile);
    }

    /**
     * Loads the bundle cache
     *
     * @param bool        $development
     * @param string|null $cacheFile
     *
     * @return ConfigInterface[]
     */
    private function loadFromCache($development, $cacheFile)
    {
        $bundleConfigs = is_file($cacheFile) ? unserialize(file_get_contents($cacheFile)) : null;

        if (!is_array($bundleConfigs) || 0 === count($bundleConfigs)) {
            $bundleConfigs = $this->loadFromPlugins($development, $cacheFile);
        }

        return $bundleConfigs;
    }

    /**
     * Generates the bundles map
     *
     * @param bool        $development
     * @param string|null $cacheFile
     *
     * @return ConfigInterface[]
     */
    private function loadFromPlugins($development, $cacheFile)
    {
        $resolver = $this->resolverFactory->create();

        /** @var BundlePluginInterface[] $plugins */
        $plugins = $this->pluginLoader->getInstancesOf(PluginLoader::BUNDLE_PLUGINS);

        foreach ($plugins as $plugin) {
            foreach ($plugin->getBundles($this->parser) as $config) {
                $resolver->add($config);
            }
        }

        $bundleConfigs = $resolver->getBundleConfigs($development);

        if (null !== $cacheFile) {
            $this->filesystem->dumpFile($cacheFile, serialize($bundleConfigs));
        }

        return $bundleConfigs;
    }
}

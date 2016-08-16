<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerBundle\Manager\Bundle\BundleAutoloader;
use Contao\ManagerBundle\Manager\Bundle\ConfigInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class ContaoKernel extends Kernel
{
    /**
     * @var array
     */
    protected $bundleConfigs = [];

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new ContaoManagerBundle()
        ];

        $this->addManagedBundles($bundles);

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/system';
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir() . '/config/config_' . $this->getEnvironment() . '.yml');
    }

    /**
     * Adds the managed bundles
     *
     * @param array $bundles
     */
    private function addManagedBundles(&$bundles)
    {
        $this->loadBundleCache();

        if (!is_array($this->bundleConfigs) || 0 === count($this->bundleConfigs)) {
            $this->bundleConfigs = $this->loadBundleConfigs();
            $this->writeBundleCache();
        }

        foreach ($this->bundleConfigs as $config) {
            $bundles[] = $config->getBundleInstance($this);
        }
    }

    /**
     * Writes the bundle cache
     */
    private function writeBundleCache()
    {
        if ($this->debug) {
            return;
        }

        if (!@mkdir($this->getCacheDir(), 0777, true) && !is_dir($this->getCacheDir())) {
            throw new \RuntimeException('Could not create cache dir at ' . $this->getCacheDir());
        }

        file_put_contents(
            $this->getCacheDir() . '/bundles.map',
            serialize($this->bundleConfigs)
        );
    }

    /**
     * Loads the bundle cache
     */
    private function loadBundleCache()
    {
        if ($this->debug || !is_file($this->getCacheDir() . '/bundles.map')) {
            return;
        }

        $this->bundleConfigs = unserialize(file_get_contents($this->getCacheDir() . '/bundles.map'));
    }

    /**
     * Generates the bundles map
     *
     * @return ConfigInterface[]
     */
    private function loadBundleConfigs()
    {
        $rootDir = $this->getRootDir();
        $autoloader = new BundleAutoloader(
            $rootDir . '/../vendor/composer/installed.json',
            $rootDir . '/modules'
        );

        return $autoloader->load('dev' === $this->getEnvironment());
    }
}

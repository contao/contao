<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\ManagerBundle\Autoload\BundleAutoloader;
use Contao\ManagerBundle\ContaoManagerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class ContaoKernel extends Kernel
{
    /**
     * @var array
     */
    protected $bundlesMap = [];

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new ContaoManagerBundle()
        ];

        $this->addAutoloadBundles($bundles);

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
     * Adds the autoload bundles
     *
     * @param array $bundles The bundles array
     */
    public function addAutoloadBundles(&$bundles)
    {
        $this->loadBundleCache();

        if (0 === count($this->bundlesMap)) {
            $this->bundlesMap = $this->generateBundlesMap();
            $this->writeBundleCache();
        }

        foreach ($this->bundlesMap as $name => $class) {
            if (null !== $class) {
                $bundles[] = new $class();
            } else {
                $bundles[] = new ContaoModuleBundle($name, $this->getRootDir());
            }
        }
    }

    /**
     * Writes the bundle cache
     */
    public function writeBundleCache()
    {
        if ($this->debug) {
            return;
        }

        if (!@mkdir($this->getCacheDir(), 0777, true) && !is_dir($this->getCacheDir())) {
            throw new \RuntimeException('Could not create cache dir at ' . $this->getCacheDir());
        }

        file_put_contents(
            $this->getCacheDir() . '/bundles.map',
            sprintf('<?php return %s;', var_export($this->bundlesMap, true))
        );
    }

    /**
     * Loads the bundle cache
     */
    public function loadBundleCache()
    {
        if ($this->debug || !is_file($this->getCacheDir() . '/bundles.map')) {
            return;
        }

        $this->bundlesMap = include $this->getCacheDir() . '/bundles.map';
    }

    /**
     * Generates the bundles map
     *
     * @return array The bundles map
     */
    protected function generateBundlesMap()
    {
        $autoloader = new BundleAutoloader($this->getRootDir());

        return $autoloader->load($this->getEnvironment());
    }
}

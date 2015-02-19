<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel;

use Contao\CoreBundle\Autoload\BundleAutoloader;
use Contao\CoreBundle\DependencyInjection\Compiler\AddBundlesToCachePass;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundleInterface;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Defines a custom Contao kernel which can autoload bundles.
 *
 * @author Leo Feyer <https://contao.org>
 */
abstract class ContaoKernel extends Kernel implements ContaoKernelInterface
{
    /**
     * @var array
     */
    protected $bundlesMap = [];

    /**
     * @var ContaoBundleInterface[]
     */
    protected $contaoBundles = [];

    /**
     * {@inheritdoc}
     */
    public function addAutoloadBundles(&$bundles)
    {
        if (empty($this->bundlesMap)) {
            $this->bundlesMap = $this->generateBundlesMap($bundles);
        }

        foreach ($this->bundlesMap as $package => $class) {
            if (null !== $class) {
                $bundles[] = new $class();
            } else {
                $bundles[] = new ContaoModuleBundle($package, $this->getRootDir());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeBundleCache()
    {
        file_put_contents(
            $this->getCacheDir() . '/bundles.map',
            sprintf('<?php return %s;', var_export($this->bundlesMap, true))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadBundleCache()
    {
        if (empty($this->bundlesMap) && is_file($this->getCacheDir() . '/bundles.map')) {
            $this->bundlesMap = include $this->getCacheDir() . '/bundles.map';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContaoBundles()
    {
        if (empty($this->contaoBundles)) {
            foreach ($this->getBundles() as $bundle) {
                if ($bundle instanceof ContaoBundleInterface) {
                    $this->contaoBundles[] = $bundle;
                }
            }
        }

        return $this->contaoBundles;
    }

    /**
     * Generates the bundles map and filters the app kernel bundles.
     *
     * @param BundleInterface[] $bundles The bundles array
     *
     * @return array The bundles map
     */
    protected function generateBundlesMap(array $bundles)
    {
        $autoloader = new BundleAutoloader($this->getRootDir(), $this->getEnvironment());
        $bundlesMap = $autoloader->load();

        foreach ($bundles as $bundle) {
            unset($bundlesMap[$bundle->getName()]);
        }

        return $bundlesMap;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildContainer()
    {
        $container = parent::buildContainer();
        $container->addCompilerPass(new AddBundlesToCachePass($this));

        return $container;
    }
}

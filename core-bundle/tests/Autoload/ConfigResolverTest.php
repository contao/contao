<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Autoload;

use Contao\CoreBundle\Autoload\Config;
use Contao\CoreBundle\Autoload\ConfigInterface;
use Contao\CoreBundle\Autoload\ConfigResolver;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the ConfigResolver class.
 *
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class ConfigResolverTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $resolver = new ConfigResolver();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ConfigResolver', $resolver);
    }

    /**
     * Tests adding a configuration object to the resolver.
     */
    public function testAddToResolver()
    {
        $resolver = new ConfigResolver();
        $config   = new Config();
        $result   = $resolver->add($config);

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ConfigResolver', $result);
    }

    /**
     * Tests the getBundlesMapForEnvironment() method.
     *
     * @param array  $configs        The configurations array
     * @param array  $expectedResult The expected result
     *
     * @dataProvider getBundlesMapForEnvironmentProvider
     */
    public function testGetBundlesMapForEnvironment($configs, $expectedResult)
    {
        $resolver = new ConfigResolver();

        foreach ($configs as $config) {
            $resolver->add($config);
        }

        $this->assertSame($expectedResult, $resolver->getBundlesMapForEnvironment('test'));
    }

    /**
     * Tests an unresolvable loading order.
     *
     * @expectedException \Contao\CoreBundle\Exception\UnresolvableLoadingOrderException
     */
    public function testUnresolvableLoadingOrder()
    {
        $resolver = new ConfigResolver();
        $config1  = $this->getConfig('name1', 'class1')->setLoadAfter(['name2']);
        $config2  = $this->getConfig('name2', 'class2')->setLoadAfter(['name1']);

        $resolver->add($config1)->add($config2);
        $resolver->getBundlesMapForEnvironment('all');
    }

    /**
     * Tests that non-existent load-after definition is ignored.
     */
    public function testNonExistentLoadAfterIsIgnored()
    {
        $resolver = new ConfigResolver();
        $config   = $this->getConfig('name', 'class')->setLoadAfter(['non-existent']);

        $resolver->add($config);

        $this->assertSame(['name' => 'class'], $resolver->getBundlesMapForEnvironment('all'));
    }

    /**
     *
     *
     * If there are two bundles (e.g. core-bundle and listing-bundle) with the
     * same load-after definition (e.g. integration-bundle), the load-after
     * definition bundle (integration-bundle) is loaded after the other two.
     */
    public function testCircularReference()
    {
        $listingBundleConfig = $this->getConfig('listing-bundle', 'listing-bundle-class');
        $coreBundleConfig = $this->getConfig('core-bundle', 'core-bundle-class');

        $integrationBundleConfigForListingBundle = $this->getConfig('integration-bundle', 'integration-bundle-class')
            ->setLoadAfter(['listing-bundle']);
        $integrationBundleConfigForCoreBundle = $this->getConfig('integration-bundle', 'integration-bundle-class')
            ->setLoadAfter(['core-bundle']);


        $resolver = new ConfigResolver();
        $resolver->add($listingBundleConfig);
        $resolver->add($integrationBundleConfigForListingBundle);
        $resolver->add($coreBundleConfig);
        $resolver->add($integrationBundleConfigForCoreBundle);

        $bundlesMap = $resolver->getBundlesMapForEnvironment('all');
        $bundlesMapKeys = array_keys($bundlesMap);

        // We have 3 bundles so the 3rd must be integration-bundle
        $this->assertSame('integration-bundle', $bundlesMapKeys[2]);
    }

    /**
     * Provides a static bundles map to test against.
     *
     * @return array The bundles map
     */
    public function getBundlesMapForEnvironmentProvider()
    {
        $config1 = $this->getConfig('name1', 'class1');
        $config2 = $this->getConfig('name2', 'class2')->setLoadAfter(['name1']);
        $config3 = $this->getConfig('name3', 'class3')->setReplace(['name1', 'name2']);
        $config4 = $this->getConfig('name4', 'class4')->setLoadAfter(['core']);
        $config5 = $this->getConfig('name5', 'class5')->setReplace(['core']);
        $config6 = $this->getConfig('name6', 'class6')->setLoadAfter(['name1', 'name2']);

        return [
            'Test default configs' => [
                [
                    $config1,
                ],
                [
                    'name1' => 'class1',
                ],
            ],
            'Test load after order' => [
                [
                    $config1,
                    $config2,
                ],
                [
                    'name1' => 'class1',
                    'name2' => 'class2',
                ],
            ],
            'Test replaces' => [
                [
                    $config1,
                    $config2,
                    $config3,
                ],
                [
                    'name3' => 'class3',
                ],
            ],
            'Test load after a bundle that is replaced by new one' => [
                [
                    $config4,
                    $config5,
                ],
                [
                    'name5' => 'class5',
                    'name4' => 'class4',
                ],
            ],
            'Test multiple load after statements' => [
                [
                    $config6,
                    $config1,
                    $config2,
                ],
                [
                    'name1' => 'class1',
                    'name2' => 'class2',
                    'name6' => 'class6',
                ],
            ],
        ];
    }

    /**
     * Creates a configuration object and returns it.
     *
     * @param string $name  The bundle name
     * @param string $class The bundle class name
     *
     * @return ConfigInterface The configuration object
     */
    private function getConfig($name, $class)
    {
        return Config::create()
            ->setName($name)
            ->setClass($class);
    }
}

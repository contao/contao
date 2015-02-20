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
     * @param string $env            The environment
     * @param array  $configs        The configurations array
     * @param array  $expectedResult The expected result
     *
     * @dataProvider getBundlesMapForEnvironmentProvider
     */
    public function testGetBundlesMapForEnvironment($env, $configs, $expectedResult)
    {
        $resolver = new ConfigResolver();

        foreach ($configs as $config) {
            $resolver->add($config);
        }

        $actualResult = $resolver->getBundlesMapForEnvironment($env);

        $this->assertSame($expectedResult, $actualResult);
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

        return [
            'Test default configs' => [
                'dev',
                [
                    $config1,
                ],
                [
                    'name1' => 'class1',
                ],
            ],
            'Test load after order' => [
                'dev',
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
                'dev',
                [
                    $config1,
                    $config2,
                    $config3,
                ],
                [
                    'name3' => 'class3',
                ],
            ],
            'Test load after a bundle that does not exist but is replaced by new one' => [
                'dev',
                [
                    $config4,
                    $config5,
                ],
                [
                    'name5' => 'class5',
                    'name4' => 'class4',
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

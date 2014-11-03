<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Autoload;

use Contao\CoreBundle\Autoload\Config;
use Contao\CoreBundle\Autoload\ConfigInterface;
use Contao\CoreBundle\Autoload\ConfigResolver;

/**
 * Tests the ConfigResolver class.
 *
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class ConfigResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstanceOf()
    {
        $resolver = new ConfigResolver();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ConfigResolver', $resolver);
    }

    /**
     * Tests adding a configuration object to the resolver.
     */
    public function testAdd()
    {
        $resolver = new ConfigResolver();
        $config   = new Config();
        $result   = $resolver->add($config);

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ConfigResolver', $result);
    }

    /**
     * Tests the getBundlesMapForEnvironment() method.
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
     * FIXME
     *
     * @expectedException \Contao\CoreBundle\Exception\UnresolvableLoadingOrderException
     */
    public function testCannotBeResolved()
    {
        $resolver = new ConfigResolver();

        $config1 = $this->getConfig('name1', 'class1')
            ->setLoadAfter(['name2']);

        $config2 = $this->getConfig('name2', 'class2')
            ->setLoadAfter(['name1']);

        $resolver->add($config1)->add($config2);

        $resolver->getBundlesMapForEnvironment('all');
    }

    /**
     * FIXME
     *
     * @return array The configuration array
     */
    public function getBundlesMapForEnvironmentProvider()
    {
        $config1 = $this->getConfig('name1', 'class1');

        $config2 = $this->getConfig('name2', 'class2')
            ->setLoadAfter(['name1']);

        $config3 = $this->getConfig('name3', 'class3')
            ->setReplace(['name1', 'name2']);

        $config4 = $this->getConfig('name4', 'class4')
            ->setLoadAfter(['core']);

        $config5 = $this->getConfig('name5', 'class5')
            ->setReplace(['core']);

        return [
            'Test default configs' => [
                'dev',
                [
                    $config1,
                ],
                [
                    'name1' => 'class1',
                ]
            ],
            'Test load after order' => [
                'dev',
                [
                    $config1,
                    $config2
                ],
                [
                    'name1' => 'class1',
                    'name2' => 'class2'
                ]
            ],
            'Test replaces' => [
                'dev',
                [
                    $config1,
                    $config2,
                    $config3
                ],
                [
                    'name3' => 'class3'
                ]
            ],
            'Test load after a bundle that does not exist but is replaced by new one' => [
                'dev',
                [
                    $config4,
                    $config5
                ],
                [
                    'name5' => 'class5',
                    'name4' => 'class4'
                ]
            ]
        ];
    }

    /**
     * Returns a configuration object.
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

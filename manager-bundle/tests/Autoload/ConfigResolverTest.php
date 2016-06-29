<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Autoload;

use Contao\ManagerBundle\Autoload\Config;
use Contao\ManagerBundle\Autoload\ConfigResolver;

class ConfigResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $resolver = new ConfigResolver();

        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ConfigResolver', $resolver);
    }

    public function testAdd()
    {
        $resolver = new ConfigResolver();
        $config = new Config('foobar');

        $result = $resolver->add($config);

        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\ConfigResolver', $result);
    }

    /**
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
     * @expectedException \Contao\ManagerBundle\Exception\UnresolvableLoadingOrderException
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
            'Test load after a module that does not exist but is replaced by new one' => [
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

    private function getConfig($name, $class)
    {
        $config = new Config($name);

        $config->setClass($class);

        return $config;
    }
}

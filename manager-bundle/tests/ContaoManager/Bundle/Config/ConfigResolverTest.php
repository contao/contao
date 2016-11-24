<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\ContaoManager\Bundle\Config;

use Contao\ManagerBundle\ContaoManager\Bundle\Config\BundleConfig;
use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolver;

class ConfigResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigResolver
     */
    private $resolver;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resolver = new ConfigResolver();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolver', $this->resolver);
    }

    public function testAddIsFluent()
    {
        $result = $this->resolver->add(new BundleConfig('foobar'));

        $this->assertInstanceOf(ConfigResolver::class, $result);
    }

    /**
     * @dataProvider getBundleConfigsProvider
     */
    public function testGetBundleConfigs(array $configs, array $expectedResult)
    {
        foreach ($configs as $config) {
            $this->resolver->add($config);
        }

        $actualResult = $this->resolver->getBundleConfigs(false);

        $this->assertSame($expectedResult, $actualResult);
    }

    public function testGetBundleConfigsIgnoresDevInProd()
    {
        $this->resolver->add(new BundleConfig('name1'));
        $this->resolver->add((new BundleConfig('name2'))->setLoadInProduction(false));

        $this->assertCount(1, $this->resolver->getBundleConfigs(false));
    }

    public function testGetBundleConfigsIgnoresProdInDev()
    {
        $this->resolver->add(new BundleConfig('name1'));
        $this->resolver->add((new BundleConfig('name2'))->setLoadInDevelopment(false));

        $this->assertCount(1, $this->resolver->getBundleConfigs(true));
    }

    /**
     * @expectedException \Contao\ManagerBundle\ContaoManager\Dependency\UnresolvableDependenciesException
     */
    public function testCannotBeResolved()
    {
        $this->resolver
            ->add((new BundleConfig('name1'))->setLoadAfter(['name2']))
            ->add((new BundleConfig('name2'))->setLoadAfter(['name1']))
        ;

        $this->resolver->getBundleConfigs(false);
    }

    public function getBundleConfigsProvider()
    {
        $config1 = new BundleConfig('name1');
        $config2 = (new BundleConfig('name2'))->setLoadAfter(['name1']);
        $config3 = (new BundleConfig('name3'))->setReplace(['name1', 'name2']);
        $config4 = (new BundleConfig('name4'))->setLoadAfter(['core']);
        $config5 = (new BundleConfig('name5'))->setReplace(['core']);

        return [
            'Test default configs' => [
                [
                    $config1,
                ],
                [
                    'name1' => $config1,
                ]
            ],
            'Test load after order' => [
                [
                    $config2,
                    $config1,
                ],
                [
                    'name1' => $config1,
                    'name2' => $config2
                ]
            ],
            'Test replaces' => [
                [
                    $config1,
                    $config2,
                    $config3,
                ],
                [
                    'name3' => $config3
                ]
            ],
            'Test load after a module that does not exist but is replaced by new one' => [
                [
                    $config4,
                    $config5,
                ],
                [
                    'name5' => $config5,
                    'name4' => $config4
                ]
            ],
        ];
    }
}

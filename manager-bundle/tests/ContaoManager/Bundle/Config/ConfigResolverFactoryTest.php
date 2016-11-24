<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\ContaoManager\Bundle\Config;

use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolverInterface;

class ConfigResolverFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $factory = new ConfigResolverFactory();

        $this->assertInstanceOf(ConfigResolverFactory::class, $factory);
    }

    public function testCreate()
    {
        $factory = new ConfigResolverFactory();
        $resolver = $factory->create();

        $this->assertInstanceOf(ConfigResolverInterface::class, $resolver);
    }
}

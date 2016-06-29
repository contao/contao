<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Test\Autoload;

use Contao\ManagerBundle\Autoload\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $config = new Config('foobar');

        $this->assertInstanceOf('Contao\ManagerBundle\Autoload\Config', $config);
    }

    public function testSetAndGetClass()
    {
        $config = new Config('foobar');
        $config->setClass('foobar');

        $this->assertSame('foobar', $config->getClass());
    }

    public function testSetAndGetName()
    {
        $config = new Config('foobar');

        $this->assertSame('foobar', $config->getName());
    }

    public function testSetAndGetReplace()
    {
        $config = new Config('foobar');
        $config->setReplace(['foobar']);

        $this->assertSame(['foobar'], $config->getReplace());
    }

    public function testSetAndGetEnvironments()
    {
        $config = new Config('foobar');
        $config->setEnvironments(['foobar']);

        $this->assertSame(['foobar'], $config->getEnvironments());
    }

    public function testSetAndGetLoadAfter()
    {
        $config = new Config('foobar');
        $config->setLoadAfter(['foobar']);

        $this->assertSame(['foobar'], $config->getLoadAfter());
    }
}

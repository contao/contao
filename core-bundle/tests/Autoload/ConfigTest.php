<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Autoload;

use Contao\CoreBundle\Autoload\Config;

/**
 * Tests the Config class.
 *
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $config = new Config();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\Config', $config);
    }

    /**
     * Tests the class name getter and setter.
     */
    public function testSetAndGetClass()
    {
        $config = new Config();
        $config->setClass('foobar');

        $this->assertSame('foobar', $config->getClass());
    }

    /**
     * Tests the bundle name getter and setter.
     */
    public function testSetAndGetName()
    {
        $config = new Config();
        $config->setName('foobar');

        $this->assertSame('foobar', $config->getName());
    }

    /**
     * Tests the replaces getter and setter.
     */
    public function testSetAndGetReplace()
    {
        $config = new Config();
        $config->setReplace(['foobar']);

        $this->assertSame(['foobar'], $config->getReplace());
    }

    /**
     * Tests the environments getter and setter.
     */
    public function testSetAndGetEnvironments()
    {
        $config = new Config();
        $config->setEnvironments(['foobar']);

        $this->assertSame(['foobar'], $config->getEnvironments());
    }

    /**
     * Tests the load-after getter and setter.
     */
    public function testSetAndGetLoadAfter()
    {
        $config = new Config();
        $config->setLoadAfter(['foobar']);

        $this->assertSame(['foobar'], $config->getLoadAfter());
    }
}

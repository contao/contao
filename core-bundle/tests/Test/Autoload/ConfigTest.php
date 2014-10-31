<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Test\Autoload;

use Contao\CoreBundle\Autoload\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $config = new Config();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\Config', $config);
    }

    public function testSetAndGetClass()
    {
        $config = new Config();
        $config->setClass('foobar');

        $this->assertSame('foobar', $config->getClass());
    }

    public function testSetAndGetName()
    {
        $config = new Config();
        $config->setName('foobar');

        $this->assertSame('foobar', $config->getName());
    }

    public function testSetAndGetReplace()
    {
        $config = new Config();
        $config->setReplace(['foobar']);

        $this->assertSame(['foobar'], $config->getReplace());
    }

    public function testSetAndGetEnvironments()
    {
        $config = new Config();
        $config->setEnvironments(['foobar']);

        $this->assertSame(['foobar'], $config->getEnvironments());
    }

    public function testSetAndGetLoadAfter()
    {
        $config = new Config();
        $config->setLoadAfter(['foobar']);

        $this->assertSame(['foobar'], $config->getLoadAfter());
    }
}

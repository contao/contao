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

use Contao\CoreBundle\Autoload\ConfigFactory;

class ConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanceOf()
    {
        $factory = new ConfigFactory();

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ConfigFactory', $factory);
    }

    public function testCreate()
    {
        $factory = new ConfigFactory();

        $configObject = $factory->create([
            'name'          => 'name-foobar',
            'class'         => 'class-foobar',
            'replace'       => ['foobar'],
            'environments'  => ['foobar'],
            'load-after'    => ['foobar']
        ]);

        $this->assertInstanceOf('Contao\CoreBundle\Autoload\ConfigInterface', $configObject);
    }
}

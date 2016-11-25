<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Test\ContaoManager;

use Contao\CalendarBundle\ContaoManager\Plugin;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;

/**
 * Tests the Plugin class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $plugin = new Plugin();

        $this->assertInstanceOf('Contao\CalendarBundle\ContaoManager\Plugin', $plugin);
    }

    /**
     * Tests the getBundles() method.
     */
    public function testGetBundles()
    {
        $parser = $this->getMock('Contao\ManagerPlugin\Bundle\Parser\ParserInterface');

        /** @var BundleConfig $config */
        $config = (new Plugin())->getBundles($parser)[0];

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\BundleConfig', $config);
        $this->assertEquals('Contao\CalendarBundle\ContaoCalendarBundle', $config->getName());
        $this->assertEquals(['Contao\CoreBundle\ContaoCoreBundle'], $config->getLoadAfter());
        $this->assertEquals(['calendar'], $config->getReplace());
    }
}

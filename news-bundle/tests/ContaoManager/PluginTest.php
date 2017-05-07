<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\NewsBundle\ContaoManager\Plugin;

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

        $this->assertInstanceOf('Contao\NewsBundle\ContaoManager\Plugin', $plugin);
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
        $this->assertSame('Contao\NewsBundle\ContaoNewsBundle', $config->getName());
        $this->assertSame(['Contao\CoreBundle\ContaoCoreBundle'], $config->getLoadAfter());
        $this->assertSame(['news'], $config->getReplace());
    }
}

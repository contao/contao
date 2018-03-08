<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\FaqBundle\ContaoFaqBundle;
use Contao\FaqBundle\ContaoManager\Plugin;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Plugin class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PluginTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $plugin = new Plugin();

        $this->assertInstanceOf('Contao\FaqBundle\ContaoManager\Plugin', $plugin);
    }

    /**
     * Tests returning the bundles.
     */
    public function testReturnsTheBundles()
    {
        $parser = $this->createMock(ParserInterface::class);

        /** @var BundleConfig $config */
        $config = (new Plugin())->getBundles($parser)[0];

        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\Config\BundleConfig', $config);
        $this->assertSame(ContaoFaqBundle::class, $config->getName());
        $this->assertSame([ContaoCoreBundle::class], $config->getLoadAfter());
        $this->assertSame(['faq'], $config->getReplace());
    }
}

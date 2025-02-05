<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\NewsBundle\ContaoManager\Plugin;
use Contao\NewsBundle\ContaoNewsBundle;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testReturnsTheBundles(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $config = (new Plugin())->getBundles($parser)[0];

        $this->assertInstanceOf(BundleConfig::class, $config);
        $this->assertSame(ContaoNewsBundle::class, $config->getName());
        $this->assertSame([ContaoCoreBundle::class], $config->getLoadAfter());
        $this->assertSame(['news'], $config->getReplace());
    }
}

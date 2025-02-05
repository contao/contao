<?php

declare(strict_types=1);

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

class PluginTest extends TestCase
{
    public function testReturnsTheBundles(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $config = (new Plugin())->getBundles($parser)[0];

        $this->assertInstanceOf(BundleConfig::class, $config);
        $this->assertSame(ContaoFaqBundle::class, $config->getName());
        $this->assertSame([ContaoCoreBundle::class], $config->getLoadAfter());
        $this->assertSame(['faq'], $config->getReplace());
    }
}

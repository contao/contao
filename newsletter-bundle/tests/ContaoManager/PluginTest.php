<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\NewsletterBundle\ContaoManager\Plugin;
use Contao\NewsletterBundle\ContaoNewsletterBundle;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testReturnsTheBundles(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $config = (new Plugin())->getBundles($parser)[0];

        $this->assertInstanceOf(BundleConfig::class, $config);
        $this->assertSame(ContaoNewsletterBundle::class, $config->getName());
        $this->assertSame([ContaoCoreBundle::class], $config->getLoadAfter());
        $this->assertSame(['newsletter'], $config->getReplace());
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace ContaoManager;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CommentsBundle\ContaoManager\Plugin;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\FaqBundle\ContaoFaqBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\NewsBundle\ContaoNewsBundle;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testReturnsTheBundleConfiguration(): void
    {
        $parser = $this->createMock(ParserInterface::class);

        /** @var BundleConfig $config */
        $config = (new Plugin())->getBundles($parser)[0];

        $plugins = [
            ContaoCalendarBundle::class,
            ContaoCoreBundle::class,
            ContaoFaqBundle::class,
            ContaoNewsBundle::class,
        ];

        sort($plugins);

        $this->assertInstanceOf(BundleConfig::class, $config);
        $this->assertSame($plugins, $config->getLoadAfter());
        $this->assertSame(['comments'], $config->getReplace());
    }
}

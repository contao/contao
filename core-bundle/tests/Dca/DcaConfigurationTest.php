<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Dca;

use Contao\CoreBundle\Dca\DcaConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DcaConfigurationTest extends TestCase
{
    public function testImplementsConfigurationInterface(): void
    {
        $configuration = new DcaConfiguration('tl_foo');

        $this->assertInstanceOf(ConfigurationInterface::class, $configuration);
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\System;
use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
    public function testFormatsANumber(): void
    {
        $number = '12004.34564';

        // Override the settings
        $GLOBALS['TL_LANG']['MSC']['decimalSeparator'] = '.';
        $GLOBALS['TL_LANG']['MSC']['thousandsSeparator'] = '';

        $numbers = [
            0 => '12004',
            1 => '12004.3',
            2 => '12004.35',
            3 => '12004.346',
            4 => '12004.3456',
            5 => '12004.34564',
        ];

        foreach ($numbers as $decimals => $formatted) {
            $this->assertSame(System::getFormattedNumber($number, $decimals), $formatted);
        }

        // Override the thousands separator
        $GLOBALS['TL_LANG']['MSC']['thousandsSeparator'] = ',';

        $numbers = [
            0 => '12,004',
            1 => '12,004.3',
            2 => '12,004.35',
            3 => '12,004.346',
            4 => '12,004.3456',
            5 => '12,004.34564',
        ];

        foreach ($numbers as $decimals => $formatted) {
            $this->assertSame(System::getFormattedNumber($number, $decimals), $formatted);
        }
    }

    public function testAnonymizesIpAddresses(): void
    {
        $ipv4 = '172.16.254.112';
        $ipv6 = '2001:0db8:85a3:0042:0000:8a2e:0370:7334';

        $this->assertSame('172.16.254.0', System::anonymizeIp($ipv4));
        $this->assertSame('2001:0db8:85a3:0042:0000:8a2e:0370:0000', System::anonymizeIp($ipv6));
    }
}

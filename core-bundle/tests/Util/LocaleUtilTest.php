<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Util;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\LocaleUtil;

class LocaleUtilTest extends TestCase
{
    /**
     * @dataProvider getFallbacks
     */
    public function testGetFallbacks(string $locale, array $expected): void
    {
        $this->assertSame($expected, LocaleUtil::getFallbacks($locale));
        $this->assertSame($expected, LocaleUtil::getFallbacks(strtolower($locale)));
        $this->assertSame($expected, LocaleUtil::getFallbacks(strtoupper($locale)));
        $this->assertSame($expected, LocaleUtil::getFallbacks(str_replace('_', '-', $locale)));
    }

    public static function getFallbacks(): iterable
    {
        yield ['de', ['de']];
        yield ['de_DE', ['de', 'de_DE']];
        yield ['zh_Hant_TW', ['zh', 'zh_TW', 'zh_Hant', 'zh_Hant_TW']];
        yield ['', []];
        yield ['_', []];
        yield ['__', []];
        yield ['und', []];
        yield ['und_DE', ['_DE']];
        yield ['und_Hant', ['_Hant']];
        yield ['und_Hant_TW', ['_TW', '_Hant', '_Hant_TW']];
        yield ['0', ['0']];
        yield ['00', ['00']];
        yield ['_00', ['_00']];
        yield ['_000', ['_000']];
    }
}

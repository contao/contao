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
     * @dataProvider getLocales
     */
    public function testCanonicalize(string $locale, string $canonicalized, string $primary): void
    {
        $this->assertSame($canonicalized, LocaleUtil::canonicalize($locale));
        $this->assertSame($canonicalized, LocaleUtil::canonicalize($canonicalized));
    }

    /**
     * @dataProvider getLocales
     */
    public function testGetPrimaryLanguage(string $locale, string $canonicalized, string $primary): void
    {
        $this->assertSame($primary, LocaleUtil::getPrimaryLanguage($locale));
        $this->assertSame($primary, LocaleUtil::getPrimaryLanguage($canonicalized));
        $this->assertSame($primary, LocaleUtil::getPrimaryLanguage($primary));
    }

    public static function getLocales(): iterable
    {
        yield ['de', 'de', 'de'];
        yield ['de_DE', 'de_DE', 'de'];
        yield ['zh_Hant_TW', 'zh_Hant_TW', 'zh'];
        yield ['DE', 'de', 'de'];
        yield ['De', 'de', 'de'];
        yield ['de-de', 'de_DE', 'de'];
        yield ['DE-DE', 'de_DE', 'de'];
        yield ['zh-hant-tw', 'zh_Hant_TW', 'zh'];
        yield ['_', '', ''];
        yield ['__', '', ''];
        yield ['und', '', ''];
        yield ['und_DE', '_DE', ''];
        yield ['und_Hant', '_Hant', ''];
        yield ['und_Hant_TW', '_Hant_TW', ''];
        yield ['0', '0', '0'];
        yield ['00', '00', '00'];
        yield ['_00', '_00', ''];
        yield ['_000', '_000', ''];
    }

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

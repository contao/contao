<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Intl;

use Contao\ArrayUtil;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LocalesTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        \Locale::setDefault('und');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        \Locale::setDefault('');
        unset($GLOBALS['TL_HOOKS']);
    }

    public function testGetsLocaleIds(): void
    {
        $localeIds = $this->getLocalesService()->getLocaleIds();

        $this->assertIsArray($localeIds);
        $this->assertNotEmpty($localeIds);
        $this->assertFalse(ArrayUtil::isAssoc($localeIds));

        foreach ($localeIds as $localeId) {
            $this->assertRegExp('/^[a-z]{2}/', $localeId);
        }
    }

    public function testGetsLanguageLocaleIds(): void
    {
        $localeIds = $this->getLocalesService()->getLanguageLocaleIds();

        $this->assertIsArray($localeIds);
        $this->assertNotEmpty($localeIds);
        $this->assertFalse(ArrayUtil::isAssoc($localeIds));

        foreach ($localeIds as $localeId) {
            $this->assertEmpty(\Locale::getRegion($localeId), $localeId.' should have no region');
        }
    }

    public function testGetsLocales(): void
    {
        $locales = $this->getLocalesService()->getLocales('de');

        $this->assertIsArray($locales);
        $this->assertNotEmpty($locales);
        $this->assertTrue(ArrayUtil::isAssoc($locales));

        foreach ($locales as $localeId => $label) {
            $this->assertRegExp('/^[a-z]{2}/', $localeId);
            $this->assertNotEmpty($label);
        }
    }

    public function testGetsLanguages(): void
    {
        $languages = $this->getLocalesService()->getLanguages('de');

        $this->assertIsArray($languages);
        $this->assertNotEmpty($languages);
        $this->assertTrue(ArrayUtil::isAssoc($languages));

        foreach ($languages as $localeId => $label) {
            $this->assertEmpty(\Locale::getRegion($localeId), $localeId.' should have no region');
            $this->assertNotEmpty($label);
        }
    }

    public function testGetsLocaleLabels(): void
    {
        $this->assertSame(
            [
                'de' => 'Deutsch',
                'de_at' => 'Deutsch (Österreich)',
                'en' => 'Englisch - English',
            ],
            $this->getLocalesService()->getDisplayNames(['en', 'de_at', 'de'], 'de', true)
        );

        $this->assertSame(
            [
                'en' => 'English',
                'de' => 'German',
                'de_at' => 'German (Austria)',
            ],
            $this->getLocalesService()->getDisplayNames(['en', 'de_at', 'de'], 'en', false)
        );

        $this->assertSame(
            [
                'gsw_Hans_AT' => 'Schwiizertüütsch (Veräifachti Chineesischi Schrift, Ööschtriich)',
                'de_CH' => 'Tüütsch (Schwiiz) - Deutsch (Schweiz)',
            ],
            $this->getLocalesService()->getDisplayNames(['gsw_Hans_AT', 'de_CH'], 'gsw', true)
        );
    }

    public function testGetsLocalesTranslated(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(
                function (string $label, array $parameters, string $domain, string $locale = null) {
                    $this->assertSame('contao_languages', $domain);
                    $this->assertSame('de', $locale);

                    if ('LNG.de' === $label) {
                        return 'Germanisch';
                    }

                    return $label;
                }
            )
        ;

        $locales = $this->getLocalesService($translator)->getLocales('de');

        $this->assertSame('Germanisch', $locales['de']);

        $positionDe = array_search('de', array_keys($locales), true);
        $positionFr = array_search('fr', array_keys($locales), true);
        $positionEl = array_search('el', array_keys($locales), true);

        // "Germanisch" should be sorted after French and before Greek
        $this->assertGreaterThan($positionFr, $positionDe);
        $this->assertLessThan($positionEl, $positionDe);
    }

    /**
     * @dataProvider getLocalesConfig
     */
    public function testGetsLocaleIdsConfigured(array $localesList, array $expected): void
    {
        $localeIds = $this->getLocalesService(null, null, null, $localesList)->getLocaleIds();

        $this->assertSame($expected, $localeIds);
    }

    /**
     * @dataProvider getLocalesConfig
     */
    public function testGetsLocalesConfigured(array $localesList, array $expected): void
    {
        $locales = $this->getLocalesService(null, null, null, $localesList)->getLocales('de');

        $localeIds = array_keys($locales);
        sort($localeIds);

        $this->assertSame($expected, $localeIds);

        foreach ($locales as $localeId => $localeLabel) {
            $this->assertRegExp('/^[a-z]{2}/', $localeId);
            $this->assertNotEmpty($localeLabel);
        }
    }

    /**
     * @dataProvider getLocalesConfig
     */
    public function testGetsLanguagesConfigured(array $localesList, array $expected): void
    {
        $locales = $this->getLocalesService(null, null, null, $localesList)->getLanguages('de');

        $localeIds = array_keys($locales);
        sort($localeIds);

        // Remove regions
        $expected = array_values(array_unique(array_map(
            static function ($localeId) {
                return preg_replace('/_(?:[A-Z]{2}|[0-9]{3})(?=_|$)/', '', $localeId);
            },
            $expected
        )));

        $this->assertSame($expected, $localeIds);

        foreach ($locales as $localeId => $localeLabel) {
            $this->assertRegExp('/^[a-z]{2}/', $localeId);
            $this->assertNotEmpty($localeLabel);
        }
    }

    public function getLocalesConfig()
    {
        yield [
            ['en', 'de'],
            ['de', 'en'],
        ];

        yield [
            ['fr', '-en', 'de', 'en'],
            ['de', 'fr'],
        ];

        yield [
            ['-en', '+en', 'de'],
            ['de', 'en'],
        ];

        yield [
            ['+zzz_ZZ', '+zzz'],
            array_merge(\ResourceBundle::getLocales(''), ['zzz', 'zzz_ZZ']),
        ];

        yield [
            ['-en', '-de_AT'],
            array_values(array_diff(\ResourceBundle::getLocales(''), ['en', 'de_AT'])),
        ];

        yield [
            ['-en', '-de', '+en', '+de'],
            \ResourceBundle::getLocales(''),
        ];
    }

    public function testsGetsFallbackLocaleFromRequest(): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->method('getLocale')
            ->willReturn('de')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $localesService = $this->getLocalesService(null, $requestStack, null, ['de', 'de_AT', 'de_CH', 'en_US']);

        $this->assertSame([
            'de' => 'Deutsch',
            'de_AT' => 'Deutsch (Österreich)',
            'de_CH' => 'Deutsch (Schweiz)',
            'en_US' => 'Englisch (Vereinigte Staaten) - English (United States)',
        ], $localesService->getLocales(null, true));

        $this->assertSame([
            'de' => 'Deutsch',
            'en' => 'Englisch - English',
        ], $localesService->getLanguages(null, true));

        $this->assertSame([
            'de' => 'Deutsch',
            'en' => 'Englisch - English',
            'gsw' => 'Schweizerdeutsch - Schwiizertüütsch',
        ], $localesService->getDisplayNames(['gsw', 'de', 'en'], null, true));
    }

    /**
     * @group legacy
     */
    public function testAppliesLegacyHook(): void
    {
        $this->expectDeprecation('%s"getLanguages" hook has been deprecated%s');

        $GLOBALS['TL_HOOKS']['getLanguages'] = [[self::class, 'getLanguagesHook']];

        $this->assertSame(
            [
                'de' => 'Germanisch',
                'de_AT' => 'Österreichisch',
                'en_AT' => 'Terminatorisch',
                'de_Cyrl' => 'Unleserlich',
            ],
            $this->getLocalesService()->getLocales('de')
        );
    }

    /**
     * @group legacy
     */
    public function testAppliesLegacyHookToLanguages(): void
    {
        $this->expectDeprecation('%s"getLanguages" hook has been deprecated%s');

        $GLOBALS['TL_HOOKS']['getLanguages'] = [[self::class, 'getLanguagesHook']];

        $this->assertSame(
            [
                'de' => 'Germanisch',
                'en' => 'Terminatorisch',
                'de_Cyrl' => 'Unleserlich',
            ],
            $this->getLocalesService()->getLanguages('de')
        );
    }

    /**
     * @group legacy
     */
    public function testAppliesLegacyHookToLocaleLabels(): void
    {
        $this->expectDeprecation('%s"getLanguages" hook has been deprecated%s');

        $GLOBALS['TL_HOOKS']['getLanguages'] = [[self::class, 'getLanguagesHook']];

        $this->assertSame(
            [
                'de' => 'Germanisch',
                'de_AT' => 'Österreichisch',
                'en_AT' => 'Terminatorisch',
                'de_Cyrl' => 'Unleserlich',
                'be' => 'Added backend language',
            ],
            $this->getLocalesService()->getDisplayNames(['de', 'en'])
        );
    }

    /**
     * @group legacy
     */
    public function testAppliesLegacyHookToLocaleIds(): void
    {
        $this->expectDeprecation('%s"getLanguages" hook has been deprecated%s');

        $GLOBALS['TL_HOOKS']['getLanguages'] = [[self::class, 'getLanguagesHook']];

        $this->assertSame(
            ['de', 'de_AT', 'de_Cyrl', 'en_AT'],
            $this->getLocalesService()->getLocaleIds()
        );
    }

    /**
     * @group legacy
     */
    public function testAppliesLegacyHookToLanguageLocaleIds(): void
    {
        $this->expectDeprecation('%s"getLanguages" hook has been deprecated%s');

        $GLOBALS['TL_HOOKS']['getLanguages'] = [[self::class, 'getLanguagesHook']];

        $this->assertSame(
            ['de', 'de_Cyrl', 'en'],
            $this->getLocalesService()->getLanguageLocaleIds()
        );
    }

    public function getLanguagesHook(array &$return, array $languages, array $langsNative, bool $blnInstalledOnly): void
    {
        $this->assertIsArray($return);
        $this->assertNotEmpty($return);
        $this->assertTrue(ArrayUtil::isAssoc($return));

        $this->assertSame('German', $languages['de']);
        $this->assertContains($return['de'], ['Deutsch', 'German']);
        $this->assertSame('Deutsch', $langsNative['de']);
        $this->assertSame('English', $langsNative['en']);

        $return = [
            'de' => 'Germanisch',
            'de_AT' => 'Österreichisch',
            'en_AT' => 'Terminatorisch',
            'de_Cyrl' => 'Unleserlich',
        ];

        if ($blnInstalledOnly) {
            $return['be'] = 'Added backend language';
        }
    }

    private function getLocalesService(TranslatorInterface $translator = null, RequestStack $requestStack = null, ContaoFramework $contaoFramework = null, array $localesList = [], array $backendLocales = ['en']): Locales
    {
        if (null === $translator) {
            $translator = $this->createMock(TranslatorInterface::class);
            $translator
                ->method('trans')
                ->willReturnArgument(0)
            ;
        }

        if (null === $requestStack) {
            $requestStack = $this->createMock(RequestStack::class);
        }

        if (null === $contaoFramework) {
            $contaoFramework = $this->mockContaoFramework([
                System::class => new class(System::class) extends Adapter {
                    public function importStatic($class)
                    {
                        return new $class();
                    }
                },
            ]);
        }

        return new Locales($translator, $requestStack, $contaoFramework, $localesList, $backendLocales);
    }
}

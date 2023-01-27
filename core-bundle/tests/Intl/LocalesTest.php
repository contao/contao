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
use Contao\CoreBundle\Intl\Locales;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator;

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
        \Locale::setDefault('');
        ini_restore('intl.default_locale');
        unset($GLOBALS['TL_HOOKS']);

        parent::tearDown();
    }

    public function testGetsLocaleIds(): void
    {
        $localeIds = $this->getLocalesService()->getLocaleIds();

        $this->assertIsArray($localeIds);
        $this->assertNotEmpty($localeIds);
        $this->assertFalse(ArrayUtil::isAssoc($localeIds));

        foreach ($localeIds as $localeId) {
            $this->assertMatchesRegularExpression('/^[a-z]{2}/', $localeId);
        }
    }

    public function testGetsEnabledLocaleIds(): void
    {
        $this->assertSame(['en', 'de'], $this->getLocalesService()->getEnabledLocaleIds());
        $this->assertSame(
            ['gsw', 'de', 'en'],
            $this->getLocalesService(null, null, null, [], [], 'gsw')->getEnabledLocaleIds()
        );
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
            $this->assertMatchesRegularExpression('/^[a-z]{2}/', $localeId);
            $this->assertNotEmpty($label);
        }
    }

    public function testGetsEnabledLocales(): void
    {
        $this->assertSame(
            [
                'en' => 'English',
                'de' => 'German',
            ],
            $this->getLocalesService()->getEnabledLocales()
        );

        $this->assertSame(
            [
                'de' => 'Deutsch',
                'en' => 'Englisch - English',
            ],
            $this->getLocalesService()->getEnabledLocales('de', true)
        );

        $this->assertSame(
            [
                'de' => 'Deutsch',
                'en' => 'Englisch - English',
                'gsw' => 'Schweizerdeutsch - Schwiizertüütsch',
            ],
            $this->getLocalesService(null, null, null, [], [], 'gsw')->getEnabledLocales('de', true)
        );
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
            $this->getLocalesService()->getDisplayNames(['en', 'de_at', 'de'], 'en')
        );

        $this->assertSame(
            [
                'gsw_Hans_AT' => 'Schwiizertüütsch (Veräifachti Chineesischi Schrift, Ööschtriich) - Schweizerdeutsch (Vereinfacht, Österreich)',
                'de_CH' => 'Tüütsch (Schwiiz) - Deutsch (Schweiz)',
            ],
            $this->getLocalesService()->getDisplayNames(['gsw_Hans_AT', 'de_CH'], 'gsw', true)
        );
    }

    public function testGetsLocalesTranslated(): void
    {
        $catalogue = $this->createMock(MessageCatalogueInterface::class);
        $catalogue
            ->method('has')
            ->willReturnCallback(
                function (string $label, string $domain) {
                    $this->assertSame('contao_languages', $domain);

                    return 'LNG.de' === $label;
                }
            )
        ;

        $translator = $this->createMock(Translator::class);
        $translator
            ->method('getCatalogue')
            ->with('de')
            ->willReturn($catalogue)
        ;

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
    public function testGetsLocaleIdsConfigured(array $configLocales, array $expected): void
    {
        $localeIds = $this->getLocalesService(null, null, null, $configLocales)->getLocaleIds();

        $this->assertSame($expected, $localeIds);

        $localeIds = $this
            ->getLocalesService(null, null, \ResourceBundle::getLocales(''), [], $configLocales, $expected[0])
            ->getEnabledLocaleIds()
        ;

        $this->assertSame($expected, $localeIds);
    }

    /**
     * @dataProvider getLocalesConfig
     */
    public function testGetsLocalesConfigured(array $configLocales, array $expected): void
    {
        $locales = $this->getLocalesService(null, null, null, $configLocales)->getLocales('de');

        $localeIds = array_keys($locales);
        sort($localeIds);

        $this->assertSame($expected, $localeIds);

        foreach ($locales as $localeId => $localeLabel) {
            $this->assertMatchesRegularExpression('/^[a-z]{2}/', $localeId);
            $this->assertNotEmpty($localeLabel);
        }

        $locales = $this
            ->getLocalesService(null, null, \ResourceBundle::getLocales(''), [], $configLocales, $expected[0])
            ->getEnabledLocales('de')
        ;

        $localeIds = array_keys($locales);
        sort($localeIds);

        $this->assertSame($expected, $localeIds);

        foreach ($locales as $localeId => $localeLabel) {
            $this->assertMatchesRegularExpression('/^[a-z]{2}/', $localeId);
            $this->assertNotEmpty($localeLabel);
        }
    }

    /**
     * @dataProvider getLocalesConfig
     */
    public function testGetsLanguagesConfigured(array $configLocales, array $expected): void
    {
        $locales = $this->getLocalesService(null, null, null, $configLocales)->getLanguages('de');

        $localeIds = array_keys($locales);
        sort($localeIds);

        // Remove regions
        $expected = array_values(array_unique(array_map(
            static fn ($localeId) => preg_replace('/_(?:[A-Z]{2}|\d{3})(?=_|$)/', '', $localeId),
            $expected
        )));

        $this->assertSame($expected, $localeIds);

        foreach ($locales as $localeId => $localeLabel) {
            $this->assertMatchesRegularExpression('/^[a-z]{2}/', $localeId);
            $this->assertNotEmpty($localeLabel);
        }
    }

    public function getLocalesConfig(): \Generator
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
        ], $localesService->getEnabledLocales(null, true));

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

    private function getLocalesService(Translator $translator = null, RequestStack $requestStack = null, array $defaultEnabledLocales = null, array $configLocales = [], array $configEnabledLocales = [], string $defaultLocale = null): Locales
    {
        if (null === $translator) {
            $translator = $this->createMock(Translator::class);
            $translator
                ->method('getCatalogue')
                ->willReturn($this->createMock(MessageCatalogueInterface::class))
            ;
        }

        $requestStack ??= $this->createMock(RequestStack::class);

        $defaultLocales = \ResourceBundle::getLocales('');
        $defaultEnabledLocales ??= ['en', 'de'];
        $defaultLocale ??= 'en';

        return new Locales($translator, $requestStack, $defaultLocales, $defaultEnabledLocales, $configLocales, $configEnabledLocales, $defaultLocale);
    }
}

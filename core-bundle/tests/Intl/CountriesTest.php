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

use Contao\CoreBundle\Intl\Countries;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Intl\Countries as SymfonyCountries;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator;

class CountriesTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_HOOKS']);
    }

    public function testGetsCountryCodes(): void
    {
        $countryCodes = $this->getCountriesService()->getCountryCodes();

        $this->assertNotEmpty($countryCodes);

        /** @phpstan-ignore function.alreadyNarrowedType */
        $this->assertTrue(array_is_list($countryCodes));

        foreach ($countryCodes as $countryCode) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $countryCode);
        }
    }

    public function testGetsCountryNames(): void
    {
        $countryNames = $this->getCountriesService()->getCountries('en');

        $this->assertNotEmpty($countryNames);

        foreach ($countryNames as $countryCode => $countryName) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $countryCode);
            $this->assertNotEmpty($countryName);
            $this->assertNotSame($countryCode, $countryName);
        }
    }

    public function testGetsCountryNamesTranslated(): void
    {
        $catalogue = $this->createMock(MessageCatalogueInterface::class);
        $catalogue
            ->method('has')
            ->willReturnCallback(
                function (string $label, string $domain) {
                    $this->assertSame('contao_countries', $domain);

                    return 'CNT.de' === $label || 'CNT.at9' === $label;
                },
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
                function (string $label, array $parameters, string $domain, string|null $locale = null) {
                    $this->assertSame('contao_countries', $domain);
                    $this->assertSame('de', $locale);

                    if ('CNT.de' === $label) {
                        return 'Schland';
                    }

                    if ('CNT.at9' === $label) {
                        return 'Wien';
                    }

                    return $label;
                },
            )
        ;

        $countryNames = $this->getCountriesService($translator, ['+AT-5', '+AT-9', '+DE-BE'])->getCountries('de');

        $this->assertSame('Schland', $countryNames['DE']);
        $this->assertSame('Wien', $countryNames['AT-9']);
        $this->assertSame('Schland (DE-BE)', $countryNames['DE-BE']);
        $this->assertSame('Österreich (AT-5)', $countryNames['AT-5']);

        $positionDe = array_search('DE', array_keys($countryNames), true);
        $positionRu = array_search('RU', array_keys($countryNames), true);
        $positionTr = array_search('TR', array_keys($countryNames), true);

        // "Schland" should be sorted after Russia and before Turkey
        $this->assertGreaterThan($positionRu, $positionDe);
        $this->assertLessThan($positionTr, $positionDe);
    }

    #[DataProvider('getCountriesConfig')]
    public function testGetsCountryCodesConfigured(array $configCountries, array $expected): void
    {
        $countryCodes = $this->getCountriesService(null, $configCountries)->getCountryCodes();

        $this->assertSame($expected, $countryCodes);
    }

    #[DataProvider('getCountriesConfig')]
    public function testGetsCountryNamesConfigured(array $configCountries, array $expected): void
    {
        $countryNames = $this->getCountriesService(null, $configCountries)->getCountries('de');

        $countryCodes = array_keys($countryNames);
        sort($countryCodes);

        $this->assertSame($expected, $countryCodes);
        $this->assertNotSame($countryCodes, array_keys($countryNames));

        foreach ($countryNames as $countryCode => $countryName) {
            $this->assertMatchesRegularExpression('/^[A-Z]{2}(?:-[A-Z0-9]{1,3})?$/', $countryCode);
            $this->assertNotEmpty($countryName);
        }
    }

    public static function getCountriesConfig(): iterable
    {
        yield [
            ['DE', 'AT'],
            ['AT', 'DE'],
        ];

        yield [
            ['CH', '-AT', 'DE', 'AT'],
            ['CH', 'DE'],
        ];

        yield [
            ['-AT', '+AT', 'DE'],
            ['AT', 'DE'],
        ];

        yield [
            ['+ZZ', '+ZY'],
            [...SymfonyCountries::getCountryCodes(), 'ZY', 'ZZ'],
        ];

        yield [
            ['-AT', '-DE'],
            array_values(array_diff(SymfonyCountries::getCountryCodes(), ['AT', 'DE'])),
        ];

        yield [
            ['-AT', '-DE', '+AT', '+DE'],
            SymfonyCountries::getCountryCodes(),
        ];

        $codes = array_values(array_diff(SymfonyCountries::getCountryCodes(), ['AT']));
        $codes[] = 'AT-9';
        sort($codes);

        yield [
            ['-AT', '+AT-9'],
            $codes,
        ];
    }

    private function getCountriesService(Translator|null $translator = null, array $configCountries = []): Countries
    {
        if (!$translator) {
            $translator = $this->createMock(Translator::class);
            $translator
                ->method('getCatalogue')
                ->willReturn($this->createMock(MessageCatalogueInterface::class))
            ;
        }

        return new Countries($translator, SymfonyCountries::getCountryCodes(), $configCountries);
    }
}

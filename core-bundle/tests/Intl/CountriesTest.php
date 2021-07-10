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
use Contao\CoreBundle\Intl\Countries;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries as SymfonyCountries;
use Symfony\Contracts\Translation\TranslatorInterface;

class CountriesTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_HOOKS']);
    }

    public function testGetsCountryCodes(): void
    {
        $countryCodes = $this->getCountriesService()->getCountryCodes();

        $this->assertIsArray($countryCodes);
        $this->assertNotEmpty($countryCodes);
        $this->assertFalse(ArrayUtil::isAssoc($countryCodes));

        foreach ($countryCodes as $countryCode) {
            $this->assertRegExp('/^[A-Z]{2}$/', $countryCode);
        }
    }

    public function testGetsCountryNames(): void
    {
        $countryNames = $this->getCountriesService()->getCountries('en');

        $this->assertIsArray($countryNames);
        $this->assertNotEmpty($countryNames);
        $this->assertTrue(ArrayUtil::isAssoc($countryNames));

        foreach ($countryNames as $countryCode => $countryName) {
            $this->assertRegExp('/^[A-Z]{2}$/', $countryCode);
            $this->assertNotEmpty($countryName);
            $this->assertNotSame($countryCode, $countryName);
        }
    }

    public function testGetsCountryNamesTranslated(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(
                function (string $label, array $parameters, string $domain, string $locale = null) {
                    $this->assertSame('contao_countries', $domain);
                    $this->assertSame('de', $locale);

                    if ('CNT.de' === $label) {
                        return 'Schland';
                    }

                    return $label;
                }
            )
        ;

        $countryNames = $this->getCountriesService($translator)->getCountries('de');

        $this->assertSame('Schland', $countryNames['DE']);

        $positionDe = array_search('DE', array_keys($countryNames), true);
        $positionRu = array_search('RU', array_keys($countryNames), true);
        $positionTr = array_search('TR', array_keys($countryNames), true);

        // "Schland" should be sorted after Russia and before Turkey
        $this->assertGreaterThan($positionRu, $positionDe);
        $this->assertLessThan($positionTr, $positionDe);
    }

    /**
     * @dataProvider getCountriesConfig
     */
    public function testGetsCountryCodesConfigured(array $countriesList, array $expected): void
    {
        $countryCodes = $this->getCountriesService(null, null, null, $countriesList)->getCountryCodes();

        $this->assertSame($expected, $countryCodes);
    }

    /**
     * @dataProvider getCountriesConfig
     */
    public function testGetsCountryNamesConfigured(array $countriesList, array $expected): void
    {
        $countryNames = $this->getCountriesService(null, null, null, $countriesList)->getCountries('de');

        $countryCodes = array_keys($countryNames);
        sort($countryCodes);

        $this->assertSame($expected, $countryCodes);
        $this->assertNotSame($countryCodes, array_keys($countryNames));

        foreach ($countryNames as $countryCode => $countryName) {
            $this->assertRegExp('/^[A-Z]{2}$/', $countryCode);
            $this->assertNotEmpty($countryName);
        }
    }

    public function getCountriesConfig()
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
            array_merge(SymfonyCountries::getCountryCodes(), ['ZY', 'ZZ']),
        ];

        yield [
            ['-AT', '-DE'],
            array_values(array_diff(SymfonyCountries::getCountryCodes(), ['AT', 'DE'])),
        ];

        yield [
            ['-AT', '-DE', '+AT', '+DE'],
            SymfonyCountries::getCountryCodes(),
        ];
    }

    /**
     * @group legacy
     */
    public function testAppliesLegacyHook(): void
    {
        $this->expectDeprecation('%s"getCountries" hook has been deprecated%s');

        $GLOBALS['TL_HOOKS']['getCountries'] = [[self::class, 'getCountriesHook']];

        $countryNames = $this->getCountriesService()->getCountries('de');

        $this->assertSame([
            'DE' => 'Schland',
            'AT' => 'Austria, no kangaroos',
        ], $countryNames);
    }

    /**
     * @group legacy
     */
    public function testAppliesLegacyHookToCountryCodes(): void
    {
        $this->expectDeprecation('%s"getCountries" hook has been deprecated%s');

        $GLOBALS['TL_HOOKS']['getCountries'] = [[self::class, 'getCountriesHook']];

        $countryCodes = $this->getCountriesService()->getCountryCodes();

        $this->assertSame(['AT', 'DE'], $countryCodes);
    }

    public function getCountriesHook(array &$return, array $countries): void
    {
        $this->assertIsArray($return);
        $this->assertNotEmpty($return);
        $this->assertTrue(ArrayUtil::isAssoc($return));

        $this->assertSame('Germany', $countries['de']);
        $this->assertSame('Deutschland', $return['de']);

        $return = [
            'de' => 'Schland',
            'at' => 'Austria, no kangaroos',
        ];
    }

    private function getCountriesService(TranslatorInterface $translator = null, RequestStack $requestStack = null, ContaoFramework $contaoFramework = null, array $countriesList = []): Countries
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

        return new Countries($translator, $requestStack, $contaoFramework, $countriesList);
    }
}

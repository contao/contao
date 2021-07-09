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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Intl\Countries;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class CountriesTest extends TestCase
{
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
            $contaoFramework = $this->createMock(ContaoFramework::class);
        }

        return new Countries($translator, $requestStack, $contaoFramework, $countriesList);
    }
}

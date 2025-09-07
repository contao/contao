<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Intl;

use Contao\ArrayUtil;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Countries
{
    /**
     * @var list<string>
     */
    private readonly array $countries;

    public function __construct(
        private readonly TranslatorInterface&TranslatorBagInterface $translator,
        array $defaultCountries,
        array $configCountries,
    ) {
        $this->countries = ArrayUtil::alterListByConfig($defaultCountries, $configCountries);
    }

    /**
     * @return array<string, string> Translated country names indexed by their uppercase ISO 3166-1 alpha-2 code
     */
    public function getCountries(string|null $displayLocale = null): array
    {
        $displayLocale ??= $this->translator->getLocale();

        $countries = [];

        foreach ($this->countries as $countryCode) {
            [$country, $subdivision] = explode('-', $countryCode, 2) + [null, null];

            $langKey = 'CNT.'.strtolower($country.$subdivision);
            $langKeyShort = 'CNT.'.strtolower($country);

            if ($this->translator->getCatalogue($displayLocale)->has($langKey, 'contao_countries')) {
                $countries[$countryCode] = $this->translator->trans($langKey, [], 'contao_countries', $displayLocale);
            } elseif ($subdivision && $this->translator->getCatalogue($displayLocale)->has($langKeyShort, 'contao_countries')) {
                $countries[$countryCode] = $this->translator->trans($langKeyShort, [], 'contao_countries', $displayLocale)." ($countryCode)";
            } else {
                $countries[$countryCode] = \Locale::getDisplayRegion('_'.$countryCode, $displayLocale);

                if ($subdivision) {
                    $countries[$countryCode] .= " ($countryCode)";
                }
            }
        }

        (new \Collator($displayLocale))->asort($countries);

        return $countries;
    }

    /**
     * @return list<string> Uppercase ISO 3166-1 alpha-2 codes
     */
    public function getCountryCodes(): array
    {
        return $this->countries;
    }
}

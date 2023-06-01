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

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Countries
{
    /**
     * @var list<string>
     */
    private array $countries;

    /**
     * @param TranslatorInterface&TranslatorBagInterface $translator
     */
    public function __construct(
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
        array $defaultCountries,
        array $configCountries,
        private string $defaultLocale,
    ) {
        $this->countries = $this->filterCountries($defaultCountries, $configCountries);
    }

    /**
     * @return array<string, string> Translated country names indexed by their uppercase ISO 3166-1 alpha-2 code
     */
    public function getCountries(string|null $displayLocale = null): array
    {
        if (null === $displayLocale && null !== ($request = $this->requestStack->getCurrentRequest())) {
            $displayLocale = $request->getLocale();
        }

        $countries = [];

        foreach ($this->countries as $countryCode) {
            $langKey = 'CNT.'.strtolower($countryCode);

            if ($this->translator->getCatalogue($displayLocale)->has($langKey, 'contao_countries')) {
                $countries[$countryCode] = $this->translator->trans($langKey, [], 'contao_countries', $displayLocale);
            } else {
                $countries[$countryCode] = \Locale::getDisplayRegion('_'.$countryCode, $displayLocale ?? $this->defaultLocale);
            }
        }

        (new \Collator($displayLocale ?? $this->defaultLocale))->asort($countries);

        return $countries;
    }

    /**
     * @return list<string> Uppercase ISO 3166-1 alpha-2 codes
     */
    public function getCountryCodes(): array
    {
        return $this->countries;
    }

    /**
     * Add, remove or replace countries as configured in the container configuration.
     *
     * @return list<string>
     */
    private function filterCountries(array $countries, array $filter): array
    {
        $newList = array_filter($filter, static fn ($country) => !\in_array($country[0], ['-', '+'], true));

        if ($newList) {
            $countries = $newList;
        }

        foreach ($filter as $country) {
            $prefix = $country[0];
            $countryCode = substr($country, 1);

            if ('-' === $prefix && \in_array($countryCode, $countries, true)) {
                unset($countries[array_search($countryCode, $countries, true)]);
            } elseif ('+' === $prefix && !\in_array($countryCode, $countries, true)) {
                $countries[] = $countryCode;
            }
        }

        sort($countries);

        return array_values($countries);
    }
}

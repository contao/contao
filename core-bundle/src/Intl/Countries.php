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
use Symfony\Component\HttpFoundation\RequestStack;
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
        private readonly RequestStack $requestStack,
        array $defaultCountries,
        array $configCountries,
        private readonly string $defaultLocale,
    ) {
        $this->countries = ArrayUtil::alterListByConfig($defaultCountries, $configCountries);
    }

    /**
     * @return array<string, string> Translated country names indexed by their uppercase ISO 3166-1 alpha-2 code
     */
    public function getCountries(string|null $displayLocale = null): array
    {
        if (null === $displayLocale && ($request = $this->requestStack->getCurrentRequest())) {
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
}

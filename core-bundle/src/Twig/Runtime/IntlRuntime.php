<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Intl\Countries;
use Twig\Extension\RuntimeExtensionInterface;

final class IntlRuntime implements RuntimeExtensionInterface
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $countries = [];

    /**
     * @internal
     */
    public function __construct(private readonly Countries $countriesService)
    {
    }

    public function countryName(string|null $country, string|null $locale = null): string
    {
        if (null === $country || '' === $country) {
            return '';
        }

        $country = strtoupper($country);
        $countries = $this->countries[$locale ?? ''] ??= $this->countriesService->getCountries($locale);

        return $countries[$country] ?? \Locale::getDisplayRegion('_'.$country, $locale ?? \Locale::getDefault());
    }
}

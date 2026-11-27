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
    private array $countryNames = [];

    /**
     * @internal
     */
    public function __construct(private readonly Countries $countries)
    {
    }

    public function countryName(string|null $country, string|null $locale = null): string
    {
        if (null === $country || '' === $country) {
            return '';
        }

        $country = strtoupper($country);
        $countryNames = $this->countryNames[$locale ?? ''] ??= $this->countries->getCountries($locale);

        return $countryNames[$country] ?? \Locale::getDisplayRegion('_'.$country, $locale ?? \Locale::getDefault());
    }
}

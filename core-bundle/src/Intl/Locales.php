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

class Locales
{
    /**
     * @var array<string>
     */
    private array $locales;

    /**
     * @var array<string>
     */
    private array $enabledLocales;

    /**
     * @param TranslatorInterface&TranslatorBagInterface $translator
     */
    public function __construct(
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
        array $defaultLocales,
        array $defaultEnabledLocales,
        array $configLocales,
        array $configEnabledLocales,
        private string $defaultLocale,
    ) {
        $this->locales = $this->filterLocales($defaultLocales, $configLocales);
        $this->enabledLocales = $this->filterLocales($defaultEnabledLocales, $configEnabledLocales, $defaultLocale);
    }

    /**
     * @return array<string,string> Translated locales indexed by their ICU locale IDs
     */
    public function getLocales(string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        return $this->getDisplayNames($this->locales, $displayLocale, $addNativeSuffix);
    }

    /**
     * @return array<string,string> Translated enabled locales indexed by their ICU locale IDs
     */
    public function getEnabledLocales(string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        return $this->getDisplayNames($this->enabledLocales, $displayLocale, $addNativeSuffix);
    }

    /**
     * @return array<string,string> Translated languages (without regions) indexed by their ICU locale IDs
     */
    public function getLanguages(string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        if (null === $displayLocale && null !== ($request = $this->requestStack->getCurrentRequest())) {
            $displayLocale = $request->getLocale();
        }

        return $this->getDisplayNames($this->getLanguageLocaleIds(), $displayLocale, $addNativeSuffix);
    }

    /**
     * @return array<string> ICU locale IDs
     */
    public function getLocaleIds(): array
    {
        return $this->locales;
    }

    /**
     * @return array<string> ICU locale IDs
     */
    public function getEnabledLocaleIds(): array
    {
        return $this->enabledLocales;
    }

    /**
     * @return array<string> Languages (without regions) as ICU locale IDs
     */
    public function getLanguageLocaleIds(): array
    {
        $localeIds = array_map(
            static function ($localeId) {
                return \Locale::composeLocale(
                    array_intersect_key(
                        \Locale::parseLocale($localeId),
                        [\Locale::LANG_TAG => null, \Locale::SCRIPT_TAG => null],
                    )
                );
            },
            $this->locales
        );

        $localeIds = array_values(array_unique(array_filter($localeIds)));

        sort($localeIds);

        return $localeIds;
    }

    /**
     * @return array<string,string> Translated locales indexed by their ICU locale IDs
     */
    public function getDisplayNames(array $localeIds, string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        if (null === $displayLocale && null !== ($request = $this->requestStack->getCurrentRequest())) {
            $displayLocale = $request->getLocale();
        }

        $locales = [];

        foreach ($localeIds as $localeId) {
            $langKey = 'LNG.'.$localeId;

            if ($this->translator->getCatalogue($displayLocale)->has($langKey, 'contao_languages')) {
                $label = $this->translator->trans($langKey, [], 'contao_languages', $displayLocale);
            } else {
                $label = \Locale::getDisplayName($localeId, $displayLocale ?? $this->defaultLocale);
            }

            if ($addNativeSuffix) {
                $nativeLabel = \Locale::getDisplayName($localeId, $localeId);

                if ($nativeLabel !== $label) {
                    $label .= ' - '.$nativeLabel;
                }
            }

            $locales[$localeId] = $label;
        }

        (new \Collator($displayLocale ?? $this->defaultLocale))->asort($locales);

        return $locales;
    }

    /**
     * Add, remove or replace locales as configured in the container configuration.
     */
    private function filterLocales(array $locales, array $filter, string $default = null): array
    {
        $newList = array_filter($filter, static fn ($locale) => !\in_array($locale[0], ['-', '+'], true));

        if ($newList) {
            $locales = $newList;
        }

        foreach ($filter as $locale) {
            $prefix = $locale[0];
            $localeId = substr($locale, 1);

            if ('-' === $prefix && \in_array($localeId, $locales, true)) {
                unset($locales[array_search($localeId, $locales, true)]);
            } elseif ('+' === $prefix && !\in_array($localeId, $locales, true)) {
                $locales[] = $localeId;
            }
        }

        sort($locales);

        // The default locale must be the first supported language (see contao/core#6533)
        if (null !== $default) {
            if (\in_array($default, $locales, true)) {
                unset($locales[array_search($default, $locales, true)]);
            }
            array_unshift($locales, $default);
        }

        return $locales;
    }
}

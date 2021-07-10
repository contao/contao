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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class Locales
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFramework
     */
    private $contaoFramework;

    /**
     * @var array<string>
     */
    private $localesList;

    /**
     * @var array<string>
     */
    private $backendLocales;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack, ContaoFramework $contaoFramework, array $localesList, array $backendLocales)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->contaoFramework = $contaoFramework;
        $this->localesList = $localesList;
        $this->backendLocales = $backendLocales;
    }

    /**
     * @return array<string,string> Translated locales indexed by their ICU locale IDs
     */
    public function getLocales(string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        if (null === $displayLocale && null !== ($request = $this->requestStack->getCurrentRequest())) {
            $displayLocale = $request->getLocale();
        }

        $locales = $this->getDisplayNamesWithoutHook($this->getLocaleIdsWithoutHook(), $displayLocale, $addNativeSuffix);

        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            return $this->applyLegacyHook($locales);
        }

        return $locales;
    }

    /**
     * @return array<string,string> Translated languages (without regions) indexed by their ICU locale IDs
     */
    public function getLanguages(string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        // If the legacy hook is used, it might add or remove locales
        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            $allLocales = $this->getLocales($displayLocale, $addNativeSuffix);
            $locales = [];

            foreach ($allLocales as $localeId => $localeLabel) {
                $locale = \Locale::parseLocale($localeId);

                if (!isset($locale[\Locale::REGION_TAG])) {
                    $locales[$localeId] = $localeLabel;
                    continue;
                }

                unset($locale[\Locale::REGION_TAG]);
                $languageId = \Locale::composeLocale($locale);

                if (!isset($allLocales[$languageId])) {
                    $locales[$languageId] = $localeLabel;
                }
            }

            return $locales;
        }

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
        // If the legacy hook is used, it might add or remove locales
        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            $locales = array_keys($this->getLocales('en'));
            sort($locales);

            return $locales;
        }

        return $this->getLocaleIdsWithoutHook();
    }

    /**
     * @return array<string> Languages (without regions) as ICU locale IDs
     */
    public function getLanguageLocaleIds(): array
    {
        // If the legacy hook is used, it might add or remove locales
        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            $locales = array_keys($this->getLanguages('en'));
            sort($locales);

            return $locales;
        }

        $localeIds = array_map(
            static function ($localeId) {
                $locale = \Locale::parseLocale($localeId);

                if (!isset($locale[\Locale::REGION_TAG])) {
                    return $localeId;
                }

                unset($locale[\Locale::REGION_TAG]);

                return \Locale::composeLocale($locale);
            },
            $this->getLocaleIdsWithoutHook()
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
        $locales = $this->getDisplayNamesWithoutHook($localeIds, $displayLocale, $addNativeSuffix);

        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            return $this->applyLegacyHook($locales, true);
        }

        return $locales;
    }

    private function getDisplayNamesWithoutHook(array $localeIds, string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        if (null === $displayLocale && null !== ($request = $this->requestStack->getCurrentRequest())) {
            $displayLocale = $request->getLocale();
        }

        $locales = [];

        foreach ($localeIds as $localeId) {
            $langKey = 'LNG.'.$localeId;
            $label = $this->translator->trans($langKey, [], 'contao_languages', $displayLocale);

            if ($label === $langKey || !\is_string($label) || '' === $label) {
                $label = \Locale::getDisplayName($localeId, $displayLocale ?? 'en');
            }

            if ($addNativeSuffix) {
                $nativeLabel = \Locale::getDisplayName($localeId, $localeId);

                if ($nativeLabel !== $label) {
                    $label .= ' - '.$nativeLabel;
                }
            }

            $locales[$localeId] = $label;
        }

        (new \Collator($displayLocale ?? 'en'))->asort($locales);

        return $locales;
    }

    private function getLocaleIdsWithoutHook(): array
    {
        $locales = array_values(array_unique(array_merge($this->backendLocales, \ResourceBundle::getLocales(''))));
        $locales = $this->filterLocales($locales);

        sort($locales);

        return $locales;
    }

    /**
     * Add, remove or replace locales as configured in the container configuration.
     */
    private function filterLocales(array $locales): array
    {
        $newList = array_filter(
            $this->localesList,
            static function ($locale) {
                return !\in_array($locale[0], ['-', '+'], true);
            }
        );

        if ($newList) {
            $locales = $newList;
        }

        foreach ($this->localesList as $locale) {
            $prefix = $locale[0];
            $localeId = substr($locale, 1);

            if ('-' === $prefix && \in_array($localeId, $locales, true)) {
                unset($locales[array_search($localeId, $locales, true)]);
            } elseif ('+' === $prefix && !\in_array($localeId, $locales, true)) {
                $locales[] = $localeId;
            }
        }

        return $locales;
    }

    private function applyLegacyHook(array $return, bool $installedOnly = false)
    {
        trigger_deprecation('contao/core-bundle', '4.12', 'Using the "getLanguages" hook has been deprecated and will no longer work in Contao 5.0. Decorate the %s service instead.', __CLASS__);

        $locales = $this->getLocaleIdsWithoutHook();

        $languages = array_map(
            static function ($locale) {
                return \Locale::getDisplayName($locale, 'en') ?: $locale;
            },
            array_combine($locales, $locales)
        );

        $langsNative = array_map(
            static function ($locale) {
                return \Locale::getDisplayName($locale, $locale) ?: $locale;
            },
            array_combine($locales, $locales)
        );

        foreach ($GLOBALS['TL_HOOKS']['getLanguages'] as $callback) {
            $this->contaoFramework->getAdapter(System::class)->importStatic($callback[0])->{$callback[1]}($return, $languages, $langsNative, $installedOnly);
        }

        return $return;
    }
}

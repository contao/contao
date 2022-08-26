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
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Locales
{
    private RequestStack $requestStack;
    private ContaoFramework $contaoFramework;
    private string $defaultLocale;

    /**
     * @var TranslatorInterface&TranslatorBagInterface
     */
    private $translator;

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
    public function __construct(TranslatorInterface $translator, RequestStack $requestStack, ContaoFramework $contaoFramework, array $defaultLocales, array $defaultEnabledLocales, array $configLocales, array $configEnabledLocales, string $defaultLocale)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->contaoFramework = $contaoFramework;
        $this->locales = $this->filterLocales($defaultLocales, $configLocales);
        $this->enabledLocales = $this->filterLocales($defaultEnabledLocales, $configEnabledLocales, $defaultLocale);
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @return array<string,string> Translated locales indexed by their ICU locale IDs
     */
    public function getLocales(string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        $locales = $this->getDisplayNamesWithoutHook($this->locales, $displayLocale, $addNativeSuffix);

        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            return $this->applyLegacyHook($locales);
        }

        return $locales;
    }

    /**
     * @return array<string,string> Translated enabled locales indexed by their ICU locale IDs
     */
    public function getEnabledLocales(string $displayLocale = null, bool $addNativeSuffix = false): array
    {
        $locales = $this->getDisplayNamesWithoutHook($this->enabledLocales, $displayLocale, $addNativeSuffix);

        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            return $this->applyLegacyHook($locales, true);
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

        return $this->getDisplayNamesWithoutHook($this->getLanguageLocaleIds(), $displayLocale, $addNativeSuffix);
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

        return $this->locales;
    }

    /**
     * @return array<string> ICU locale IDs
     */
    public function getEnabledLocaleIds(): array
    {
        // If the legacy hook is used, it might add or remove locales
        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            $locales = array_keys($this->getEnabledLocales('en'));
            sort($locales);

            return $locales;
        }

        return $this->enabledLocales;
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
        $locales = $this->getDisplayNamesWithoutHook($localeIds, $displayLocale, $addNativeSuffix);

        if (!empty($GLOBALS['TL_HOOKS']['getLanguages'])) {
            $locales = $this->applyLegacyHook($locales, true);

            // Remove locale IDs potentially added by the hook
            $locales = array_filter(
                $locales,
                static fn ($locale) => \in_array($locale, $localeIds, true),
                ARRAY_FILTER_USE_KEY
            );
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

    private function applyLegacyHook(array $return, bool $installedOnly = false): array
    {
        trigger_deprecation('contao/core-bundle', '4.12', 'Using the "getLanguages" hook has been deprecated and will no longer work in Contao 5.0. Decorate the %s service instead.', __CLASS__);

        $languages = array_map(
            static fn ($locale) => \Locale::getDisplayName($locale, 'en') ?: $locale,
            array_combine($this->locales, $this->locales)
        );

        $langsNative = array_map(
            static fn ($locale) => \Locale::getDisplayName($locale, $locale) ?: $locale,
            array_combine($this->locales, $this->locales)
        );

        $system = $this->contaoFramework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['getLanguages'] as $callback) {
            $system->importStatic($callback[0])->{$callback[1]}($return, $languages, $langsNative, $installedOnly);
        }

        return $return;
    }
}

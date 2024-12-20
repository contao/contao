<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

/**
 * The LocaleUtil class helps in handling ICU Locale IDs and IETF Language Tags
 * (BCP 47). Both are almost identical, Locale ID uses underline (_) and Language
 * Tag uses dash (-).
 *
 * For any method, we are safely assuming the input can be any format, therefore
 * we try to handle any input the same.
 *
 * @see https://github.com/contao/core-bundle/issues/233
 */
class LocaleUtil
{
    /**
     * @var array<string, array<string>>
     */
    private static array $fallbacks = [];

    public static function canonicalize(string $locale): string
    {
        return \Locale::canonicalize($locale);
    }

    public static function getPrimaryLanguage(string $locale): string
    {
        return \Locale::getPrimaryLanguage($locale);
    }

    /**
     * Generates an ordered list of locales according to ICU rules, the last array
     * item is the most specific locale.
     *
     * This can also be used to load languages files that override each other. A
     * script tag (e.g. chinese traditional) always overrides a region.
     *
     * Example: "zh_Hant_TW" returns [zh, zh_TW, zh_Hant, zh_Hant_TW]
     *
     * @see https://unicode-org.github.io/icu/userguide/locale/resources.html#find-the-best-available-data
     *
     * @return array<string>
     */
    public static function getFallbacks(string $locale): array
    {
        if (isset(self::$fallbacks[$locale])) {
            return self::$fallbacks[$locale];
        }

        if ('' === $locale) {
            return [];
        }

        $result = [];
        $data = \Locale::parseLocale($locale);

        if (isset($data[\Locale::LANG_TAG])) {
            $result[] = $data[\Locale::LANG_TAG];
        } else {
            $data[\Locale::LANG_TAG] = '';
        }

        if (isset($data[\Locale::REGION_TAG])) {
            $result[] = $data[\Locale::LANG_TAG].'_'.$data[\Locale::REGION_TAG];
        }

        if (isset($data[\Locale::SCRIPT_TAG])) {
            $result[] = $data[\Locale::LANG_TAG].'_'.$data['script'];

            if (isset($data[\Locale::REGION_TAG])) {
                $result[] = $data[\Locale::LANG_TAG].'_'.$data[\Locale::SCRIPT_TAG].'_'.$data[\Locale::REGION_TAG];
            }
        }

        return self::$fallbacks[$locale] = $result;
    }

    /**
     * Converts a Locale ID (_) to a Language Tag (-) and strips keywords after the @ sign.
     *
     * Language Tag is used in two cases in Contao:
     *  1. The XML/HTML lang attribute.
     *  2. The _locale attribute in requests/routes, because it might be used to generate legacy routes.
     *  3. $GLOBALS['TL_LANGUAGE'] for historical reasons.
     */
    public static function formatAsLanguageTag(string $localeId): string
    {
        return str_replace('_', '-', self::formatAsLocale($localeId));
    }

    /**
     * Converts a Language Tag (-) to a Locale ID (_) and strips keywords after the @ sign.
     *
     * For historical reasons, the page language can be a Language Tag, so we need to
     * safely-convert the value before looking up language files etc.
     */
    public static function formatAsLocale(string $languageTag): string
    {
        $locales = static::getFallbacks($languageTag);

        return array_pop($locales) ?? '';
    }
}

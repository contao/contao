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
 * The LocaleUtil helps in handling ICU Locale IDs and IETF Language Tags (BCP 47).
 * Both are almost identical, Locale ID uses underline (_) and Language Tag uses dash (-).
 *
 * For any method, we are safely assuming the input can be any format, therefore we try
 * to handle any input the same.
 *
 * @see https://github.com/contao/core-bundle/issues/233
 */
class LocaleUtil
{
    public static function canonicalize(string $locale): string
    {
        return \Locale::canonicalize($locale);
    }

    /**
     * Converts an Locale ID (_) to a Language Tag (-) and strips keywords after the @ sign.
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
     * For historical reasons, the page language can be a Language Tag, so we need to safely-convert
     * the value before looking up language files etc.
     */
    public static function formatAsLocale(string $languageTag): string
    {
        return self::canonicalize(strtok($languageTag, '@'));
    }
}

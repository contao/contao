<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Translation;

final class LegacyGlobalsProcessor
{
    /**
     * Splits the translation key and returns the parts.
     */
    public static function getPartsFromKey(string $key): array
    {
        // Split the key into chunks allowing escaped dots (\.) and backslashes (\\)
        preg_match_all('/(?:\\\\[\\\\.]|[^.])++/', $key, $matches);

        $parts = preg_replace('/\\\\([\\\\.])/', '$1', $matches[0]);

        // Handle keys with dots in tl_layout
        if (preg_match('/tl_layout\.[a-z]+\.css\./', $key)) {
            $parts = [$parts[0], $parts[1].'.'.$parts[2], ...array_splice($parts, 3)];
        }

        return $parts;
    }

    /**
     * Returns a string representation of the global PHP language array.
     */
    public static function getStringRepresentation(array $parts, string $value): string
    {
        if (!$parts) {
            return '';
        }

        $string = "\$GLOBALS['TL_LANG']";

        foreach ($parts as $part) {
            $string .= '['.self::quoteKey($part).']';
        }

        return $string.' = '.self::quoteValue($value).";\n";
    }

    /**
     * Adds the labels to the global PHP language array.
     */
    public static function addGlobal(array $parts, string $value): void
    {
        $data = &$GLOBALS['TL_LANG'];

        foreach ($parts as $key) {
            if (!\is_array($data)) {
                $data = [];
            }

            $data = &$data[$key];
        }

        $data = $value;
    }

    private static function quoteKey(string $key): int|string
    {
        if ('0' === $key) {
            return 0;
        }

        if (is_numeric($key)) {
            return (int) $key;
        }

        return "'".str_replace("'", "\\'", $key)."'";
    }

    private static function quoteValue(string $value): string
    {
        $value = str_replace("\n", '\n', $value);

        if (str_contains($value, '\n')) {
            return '"'.str_replace(['$', '"'], ['\\$', '\\"'], $value).'"';
        }

        return "'".str_replace("'", "\\'", $value)."'";
    }
}

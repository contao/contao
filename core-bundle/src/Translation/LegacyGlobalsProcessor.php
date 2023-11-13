<?php

namespace Contao\CoreBundle\Translation;

final class LegacyGlobalsProcessor
{
    /**
     * Returns the labels from $GLOBALS['TL_LANG'] based on a translation key like "MSC.view".
     */
    public static function getFromGlobalsByKey(string $key): string|null
    {
        $parts = self::getPartsFromKey($key);
        $item = &$GLOBALS['TL_LANG'];

        foreach ($parts as $part) {
            if (!\is_array($item) || !isset($item[$part])) {
                return null;
            }

            $item = &$item[$part];
        }

        if (\is_array($item)) {
            return null;
        }

        return (string) $item;
    }

    /**
     * Splits the translation key and returns the parts.
     */
    public static function getPartsFromKey(string $key): array
    {
        // Split the key into chunks allowing escaped dots (\.) and backslashes (\\)
        preg_match_all('/(?:\\\\[\\\\.]|[^.])++/', $key, $matches);

        return preg_replace('/\\\\([\\\\.])/', '$1', $matches[0]);
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

        return $string . (' = '.self::quoteValue($value).";\n");
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

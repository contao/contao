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

use Contao\CoreBundle\Exception\ArrayStringParserException;

class ArrayString
{
    /**
     * Recursively parses a string representing an (associative) array into an
     * array.
     *
     * Format:
     *   - Arrays must be surrounded with "[" and "]"
     *   - Elements need to be separated with a ","
     *   - Keys are optional; if used, keys and values must be separated with a ":"
     *   - Keys are always treated as strings
     *   - Strings can be delimited with single or double quotes
     *   - Non delimited values are treated as int/float/bool and fallback to string
     *   - Non delimited strings can only contain "\w" characters
     *   - Floats must use the "." as decimal separator
     *
     * Examples:
     *   '[a, b]' -> [0 => 'a', 1 => 'b']
     *   '[a: "foo", b]' -> ['a' => 'foo', 1 => 'b']
     *   '[a: 2, b: true]' -> ['a' => 2, 'b' => true]
     *   '[[a, b], foo: [c]]' -> [0 => [0 => 'a', 1 => 'b' ], 'foo' => [0 => 'c']]
     */
    public static function parse(string $string): array
    {
        if (1 !== preg_match('/^\s*\[(.*)\]\s*$/', $string, $matches)) {
            throw new ArrayStringParserException('Bad format: Array notation must include outer brackets.');
        }

        $string = $matches[1];

        $result = [];
        $resultOriginal = [];
        $namedKeys = [];
        $currentElement = 0;

        $length = \strlen($string);
        $charIndex = 0;

        // Advance a single char
        $advanceOne = static function () use ($string, &$charIndex): string {
            return $string[$charIndex++];
        };

        // Advance till criteria evaluates to true and return the word in between
        $advanceUntil = static function (callable $criteria) use ($string, $length, &$charIndex): string {
            $word = '';

            for (; $charIndex < $length; ++$charIndex) {
                if ($criteria($string[$charIndex])) {
                    return $word;
                }

                $word .= $string[$charIndex];
            }

            return $word;
        };

        while ($charIndex < $length) {
            $char = $string[$charIndex];

            // Skip whitespaces
            if (' ' === $char) {
                $advanceOne();

                continue;
            }

            // Parse strings
            if (\in_array($char, ['\'', '"'], true)) {
                // Jump over starting delimiter
                $advanceOne();

                $result[$currentElement] = $advanceUntil(
                    static function (string $search) use ($char): bool {
                        return $search === $char;
                    }
                );

                // Jump over ending delimiter
                $advanceOne();

                continue;
            }

            // Parse arrays
            if ('[' === $char) {
                $depth = 0;

                $array = $advanceUntil(
                    static function (string $search) use (&$depth): bool {
                        if ('[' === $search) {
                            ++$depth;
                        } elseif (']' === $search) {
                            --$depth;
                        }

                        return ']' === $search && 0 === $depth;
                    }
                );

                // Include closing bracket
                $array .= $advanceOne();

                $result[$currentElement] = self::parse($array);

                continue;
            }

            // Handle keys
            if (':' === $char) {
                if ('' === ($key = $resultOriginal[$currentElement] ?? '')) {
                    throw new ArrayStringParserException("Bad format: Key for index $currentElement cannot be empty.");
                }

                if (\array_key_exists($key, $namedKeys)) {
                    throw new ArrayStringParserException("Bad format: Key '$key' cannot appear more than once.");
                }

                $namedKeys[$key] = $currentElement;
                unset($result[$currentElement]);

                $advanceOne();

                continue;
            }

            // Parse next item
            if (',' === $char) {
                if (!\array_key_exists($currentElement, $result)) {
                    throw new ArrayStringParserException("Bad format: Did not expect to see '$char' at position $charIndex.");
                }

                ++$currentElement;

                $advanceOne();

                continue;
            }

            // Parse values
            $value = trim($advanceUntil(
                static function (string $search): bool {
                    return 1 !== preg_match('/[^,:]/', $search);
                }
            ));

            $resultOriginal[$currentElement] = $value;
            $result[$currentElement] = self::autoCast($value);
        }

        // Resolve named keys
        foreach ($namedKeys as $key => $index) {
            if (!\array_key_exists($index, $result)) {
                throw new ArrayStringParserException("Bad format: Key '$key' is missing a value.");
            }

            $result[$key] = $result[$index];

            unset($result[$index]);
        }

        return $result;
    }

    /**
     * Detect the value type and return the typed version.
     *
     * @return int|float|bool|string|null
     */
    private static function autoCast(string $value)
    {
        if (is_numeric($value)) {
            return $value + 0;
        }

        $normalized = strtolower($value);

        if ('true' === $normalized) {
            return true;
        }

        if ('false' === $normalized) {
            return false;
        }

        if ('null' === $normalized) {
            return null;
        }

        return $value;
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Interop;

use Contao\StringUtil;
use Twig\Error\RuntimeError;

/**
 * The ContaoEscaper mimics Twig's default escape filters but prevents double
 * encoding. It must therefore ONLY be applied to templates with already encoded
 * context (input encoding)!
 *
 * This strategy will get dropped once we move to output encoding.
 *
 * @experimental
 */
final class ContaoEscaper
{
    /**
     * This implementation is a clone of Twig's html escape strategy but calls
     * htmlspecialchars with the double_encode parameter set to false.
     *
     * @see twig_escape_filter
     */
    public function escapeHtml(mixed $string, string|null $charset): string
    {
        if (null !== $charset && 'UTF-8' !== strtoupper($charset)) {
            throw new RuntimeError(\sprintf('The "contao_html" escape filter does not support the %s charset, use UTF-8 instead.', $charset));
        }

        $string = (string) $string;

        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8', false);
    }

    /**
     * This implementation is a clone of Twig's html_attr escape strategy but replaces
     * insert tags and decodes entities beforehand.
     *
     * @see twig_escape_filter
     */
    public function escapeHtmlAttr(mixed $string, string|null $charset): string
    {
        if (null !== $charset && 'UTF-8' !== strtoupper($charset)) {
            throw new RuntimeError(\sprintf('The "contao_html_attr" escape filter does not support the %s charset, use UTF-8 instead.', $charset));
        }

        $string = (string) $string;
        $string = StringUtil::decodeEntities($string);

        // Original logic
        if (!preg_match('//u', $string)) {
            throw new RuntimeError('The string to escape is not a valid UTF-8 string.');
        }

        return preg_replace_callback(
            '#[^a-zA-Z0-9,.\-_]#Su',
            static function ($matches) {
                /**
                 * This function is adapted from code coming from Zend Framework.
                 *
                 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (https://www.zend.com)
                 * @license   https://framework.zend.com/license/new-bsd New BSD License
                 */
                $chr = $matches[0];
                $ord = \ord($chr);

                // The following replaces characters undefined in HTML with the hex entity for
                // the Unicode replacement character.
                if (($ord <= 0x1F && "\t" !== $chr && "\n" !== $chr && "\r" !== $chr) || ($ord >= 0x7F && $ord <= 0x9F)) {
                    return '&#xFFFD;';
                }

                // Check if the current character to escape has a name entity we should replace
                // it with while grabbing the hex value of the character.
                if (1 === \strlen($chr)) {
                    // While HTML supports far more named entities, the lowest common denominator has
                    // become HTML5's XML Serialisation which is restricted to the those named
                    // entities that XML supports. Using HTML entities would result in this error:
                    // XML Parsing Error: undefined entity
                    static $entityMap = [
                        34 => '&quot;', /* quotation mark */
                        38 => '&amp;', /* ampersand */
                        60 => '&lt;', /* less-than sign */
                        62 => '&gt;', /* greater-than sign */
                    ];

                    return $entityMap[$ord] ?? \sprintf('&#x%02X;', $ord);
                }

                // Per OWASP recommendations, we'll use hex entities for any other characters
                // where a named entity does not exist.
                return \sprintf('&#x%04X;', mb_ord($chr, 'UTF-8'));
            },
            $string,
        );
    }
}

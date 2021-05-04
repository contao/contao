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

use Twig\Environment;

/**
 * The ContaoEscaper mimics Twig's default html escape filter but prevents
 * double encoding. It must therefore ONLY be applied to templates with already
 * encoded context (input encoding)!
 *
 * This strategy will get dropped once we move to output encoding.
 *
 * @internal
 */
class ContaoEscaper
{
    /**
     * This implementation is a clone of the html strategy inside.
     *
     * @see twig_escape_filter but calls htmlspecialchars with the
     * double_encode parameter set to false.
     */
    public function __invoke(Environment $environment, $string, ?string $charset)
    {
        $string = (string) $string;

        // see https://secure.php.net/htmlspecialchars

        // Using a static variable to avoid initializing the array
        // each time the function is called. Moving the declaration on the
        // top of the function slow downs other escaping strategies.
        static $htmlspecialcharsCharsets = [
            'ISO-8859-1' => true, 'ISO8859-1' => true,
            'ISO-8859-15' => true, 'ISO8859-15' => true,
            'utf-8' => true, 'UTF-8' => true,
            'CP866' => true, 'IBM866' => true, '866' => true,
            'CP1251' => true, 'WINDOWS-1251' => true, 'WIN-1251' => true,
            '1251' => true,
            'CP1252' => true, 'WINDOWS-1252' => true, '1252' => true,
            'KOI8-R' => true, 'KOI8-RU' => true, 'KOI8R' => true,
            'BIG5' => true, '950' => true,
            'GB2312' => true, '936' => true,
            'BIG5-HKSCS' => true,
            'SHIFT_JIS' => true, 'SJIS' => true, '932' => true,
            'EUC-JP' => true, 'EUCJP' => true,
            'ISO8859-5' => true, 'ISO-8859-5' => true, 'MACROMAN' => true,
        ];

        if (isset($htmlspecialcharsCharsets[$charset])) {
            return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset, false);
        }

        if (isset($htmlspecialcharsCharsets[strtoupper($charset)])) {
            // cache the lowercase variant for future iterations
            $htmlspecialcharsCharsets[$charset] = true;

            return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset, false);
        }

        $string = twig_convert_encoding($string, 'UTF-8', $charset);
        $string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

        return iconv('UTF-8', $charset, $string);
    }
}

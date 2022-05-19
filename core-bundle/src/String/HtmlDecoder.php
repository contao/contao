<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\String;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\StringUtil;

class HtmlDecoder
{
    public function __construct(private InsertTagParser $insertTagParser)
    {
    }

    /**
     * Converts an input-encoded string to plain text UTF-8.
     *
     * Strips or replaces insert tags, strips HTML tags, decodes entities, escapes insert tag braces.
     *
     * @see StringUtil::revertInputEncoding()
     *
     * @param bool $removeInsertTags True to remove insert tags instead of replacing them
     */
    public function inputEncodedToPlainText(string $val, bool $removeInsertTags = false): string
    {
        if ($removeInsertTags) {
            $val = StringUtil::stripInsertTags($val);
        } else {
            $val = $this->insertTagParser->replaceInline($val);
        }

        $val = strip_tags($val);
        $val = StringUtil::revertInputEncoding($val);

        return str_replace(['{{', '}}'], ['[{]', '[}]'], $val);
    }

    /**
     * Converts an HTML string to plain text with normalized white space.
     *
     * It handles all Contao input encoding specifics like insert tags, basic
     * entities and encoded entities and is meant to be used with content from
     * fields that have the allowHtml flag enabled.
     *
     * @param bool $removeInsertTags True to remove insert tags instead of replacing them
     */
    public function htmlToPlainText(string $val, bool $removeInsertTags = false): string
    {
        if (!$removeInsertTags) {
            $val = $this->insertTagParser->replaceInline($val);
        }

        // Add new lines before and after block level elements
        $val = preg_replace(
            ['/[\r\n]+/', '/<\/?(?:br|blockquote|div|dl|figcaption|figure|footer|h\d|header|hr|li|p|pre|tr)\b/i'],
            [' ', "\n$0"],
            $val
        );

        $val = $this->inputEncodedToPlainText($val, true);

        // Remove duplicate line breaks and spaces
        return trim(preg_replace(['/[^\S\n]+/', '/\s*\n\s*/'], [' ', "\n"], $val));
    }
}

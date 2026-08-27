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
    public function __construct(private readonly InsertTagParser $insertTagParser)
    {
    }

    /**
     * Converts an input-encoded string to plain text UTF-8.
     *
     * Strips or replaces insert tags, strips HTML tags, decodes entities, escapes
     * insert tag braces.
     *
     * @see StringUtil::revertInputEncoding()
     *
     * @param bool $removeInsertTags True to remove insert tags instead of replacing them, null to neither remove or replace them
     *
     * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7.
     */
    public function inputEncodedToPlainText(string $val, bool|null $removeInsertTags = false): string
    {
        trigger_deprecation('contao/core-bundle', '6.0', 'Using "%s()" is deprecated and will no longer work in Contao 7.', __METHOD__);

        if ($removeInsertTags) {
            $val = StringUtil::stripInsertTags($val);
        } elseif (false === $removeInsertTags) {
            $val = $this->insertTagParser->replaceInline($val);
        }

        return $this->stripTagsDecodeEntities($val);
    }

    /**
     * Converts an HTML string to plain text with normalized white space.
     *
     * It handles all Contao input encoding specifics like insert tags, basic entities
     * and encoded entities and is meant to be used with content from fields that have
     * the allowHtml flag enabled.
     *
     * @param bool $removeInsertTags True to remove insert tags instead of replacing them, null to neither remove or replace them
     */
    public function htmlToPlainText(string $val, bool|null $removeInsertTags = false, bool $trim = true): string
    {
        if ($removeInsertTags) {
            $val = StringUtil::stripInsertTags($val);
        } elseif (false === $removeInsertTags) {
            $val = $this->insertTagParser->replaceInline($val);
        }

        // Remove hidden elements according to
        // https://html.spec.whatwg.org/multipage/rendering.html#hidden-elements
        $val = preg_replace(
            ['/(<(area|base|basefont|datalist|head|link|meta|noembed|noframes|param|rp|script|style|template|title)\b[^>]*>).*?(<\/\2>)/is'],
            [''],
            $val,
        );

        // Add new lines before and after block level elements
        $val = preg_replace(
            ['/[\r\n]+/', '/<\/?(?:br|blockquote|div|dl|figcaption|figure|footer|h\d|header|hr|li|p|pre|tr)\b/i'],
            [' ', "\n$0"],
            $val,
        );

        $val = $this->stripTagsDecodeEntities($val);

        // Remove duplicate line breaks and spaces
        $val = preg_replace(['/[^\S\n]+/', '/\s*\n\s*/'], [' ', "\n"], $val);

        if ($trim) {
            $val = trim($val);
        }

        return $val;
    }

    private function stripTagsDecodeEntities(string $val): string
    {
        $val = strip_tags($val);
        $val = StringUtil::revertInputEncoding($val);

        return str_replace(['{{', '}}'], ['[{]', '[}]'], $val);
    }
}

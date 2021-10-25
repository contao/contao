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

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\InsertTags;
use Contao\StringUtil;

class HtmlDecoder
{
    private ContaoFramework $framework;

    /**
     * @var array<string>
     */
    private static $emptyTags = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Shortens an HTML string to a given number of characters.
     *
     * The function preserves words, so the result might be a bit shorter or
     * longer than the number of characters given. It preserves allowed tags.
     */
    public function substrHtml(string $str, int $numberOfChars): string
    {
        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        $return = '';
        $charCount = 0;
        $openTags = [];
        $tagBuffer = [];

        $str = preg_replace('/[\t\n\r]+/', ' ', $str);
        $str = strip_tags($str, $config->get('allowedTags'));
        $str = preg_replace('/ +/', ' ', $str);

        // Seperate tags and text
        $chunks = preg_split('/(<[^>]+>)/', $str, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        for ($i = 0, $c = \count($chunks); $i < $c; ++$i) {
            // Buffer tags to include them later
            if (preg_match('/<([^>]+)>/', $chunks[$i])) {
                $tagBuffer[] = $chunks[$i];
                continue;
            }

            $buffer = $chunks[$i];

            // Get the substring of the current text
            if (!$chunks[$i] = StringUtil::substr($chunks[$i], $numberOfChars - $charCount, false)) {
                break;
            }

            $blnModified = $buffer !== $chunks[$i];
            $charCount += mb_strlen(StringUtil::decodeEntities($chunks[$i]));

            if ($charCount <= $numberOfChars) {
                foreach ($tagBuffer as $tag) {
                    $tagName = strtolower(trim($tag));

                    // Extract the tag name (see #5669)
                    if (false !== ($pos = strpos($tagName, ' '))) {
                        $tagName = substr($tagName, 1, $pos - 1);
                    } else {
                        $tagName = substr($tagName, 1, -1);
                    }

                    // Skip empty tags
                    if (\in_array($tagName, self::$emptyTags, true)) {
                        continue;
                    }

                    // Store opening tags in the open_tags array
                    if (0 !== strncmp($tagName, '/', 1)) {
                        if ($i < $c || !empty($chunks[$i])) {
                            $openTags[] = $tagName;
                        }

                        continue;
                    }

                    // Closing tags will be removed from the "open tags" array
                    if ($i < $c || !empty($chunks[$i])) {
                        $openTags = array_values($openTags);

                        for ($j = \count($openTags) - 1; $j >= 0; --$j) {
                            if ($tagName === '/'.$openTags[$j]) {
                                unset($openTags[$j]);
                                break;
                            }
                        }
                    }
                }

                // If the current chunk contains text, add tags and text to the return string
                if ($i < $c || '' !== (string) ($chunks[$i] ?? null)) {
                    $return .= implode('', $tagBuffer).$chunks[$i];
                }

                // Stop after the first shortened chunk (see #7311)
                if ($blnModified) {
                    break;
                }

                $tagBuffer = [];
                continue;
            }

            break;
        }

        // Close all remaining open tags
        krsort($openTags);

        foreach ($openTags as $tag) {
            $return .= '</'.$tag.'>';
        }

        return trim($return);
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
            $val = (new InsertTags())->replace($val, false);
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
            $val = (new InsertTags())->replace($val, false);
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

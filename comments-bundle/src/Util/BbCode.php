<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Util;

use Contao\Idna;
use Contao\StringUtil;
use Contao\Validator;

/**
 * @internal
 */
final class BbCode
{
    /**
     * Converts text containing BBCode to HTML.
     *
     * Supports the following tags:
     *
     * * [b][/b] bold
     * * [i][/i] italic
     * * [u][/u] underline
     * * [code][/code]
     * * [quote][/quote]
     * * [quote=author][/quote]
     * * [url][/url]
     * * [url=https://…][/url]
     * * [email][/email]
     * * [email=name@example.com][/email]
     */
    public function toHtml(string $bbCode): string
    {
        return str_replace(['{', '}'], ['&#123;', '&#125;'], $this->compile($this->parse($this->tokenize($bbCode), $bbCode)));
    }

    /**
     * Find BBCode tokens and annotate them with their position/tag/type and
     * attribute. We're only matching tokens in the form '[tag]', '[/tag]' and
     * '[tag=attr]'.
     */
    private function tokenize(string $input): array
    {
        if (false === preg_match_all('%\[(/?)(b|i|u|quote|code|url|email|img|color)(?:=([^\[\]]*))?]%', $input, $matches, PREG_OFFSET_CAPTURE)) {
            throw new \InvalidArgumentException('Could not tokenize input.');
        }

        $tokens = [];

        foreach ($matches[0] as $index => [$token, $position]) {
            $tokens[] = [
                'start' => $position,
                'end' => $position + \strlen($token),
                'closing' => '/' === $matches[1][$index][0],
                'tag' => $matches[2][$index][0],
                'attr' => $matches[3][$index][0] ?: null,
            ];
        }

        return $tokens;
    }

    /**
     * Parses tokens into a node tree. Input before/after tokens is treated as
     * text.
     */
    private function parse(array $tokens, string $input): Node
    {
        $root = new Node();
        $node = $root;
        $tags = [];
        $position = 0;

        $addNode = static function (Node $parent, $type): Node {
            $node = new Node($parent, $type);
            $parent->children[] = $node;

            return $node;
        };

        $advance = static function (array $token) use (&$position): void {
            $position = $token['end'];
        };

        $numTokens = \count($tokens);

        for ($i = 0; $i < $numTokens; ++$i) {
            $current = $tokens[$i];

            // Text before token
            if (($length = $current['start'] - $position) > 0) {
                $addNode($node, Node::TYPE_TEXT)->setValue(substr($input, $position, $length));
            }

            // Code
            if (('code' === $current['tag']) && !$current['closing']) {
                for ($j = $i + 1; $j < $numTokens; ++$j) {
                    if ('code' === $tokens[$j]['tag'] && $tokens[$j]['closing']) {
                        $addNode($root, Node::TYPE_CODE)->setValue(substr($input, $current['end'], $tokens[$j]['start'] - $current['end']));
                        $advance($tokens[$j]);
                        $i = $j;
                        continue 2;
                    }
                }
            }

            // Blocks
            $onTagStack = \in_array($current['tag'], $tags, true);

            if (\in_array($current['tag'], ['b', 'i', 'u', 'url', 'email'], true)) {
                if (!$current['closing'] && !$onTagStack) {
                    $node = $addNode($node, Node::TYPE_BLOCK)->setTag($current['tag'])->setValue($current['attr']);
                    $tags[] = $current['tag'];
                } elseif ($current['closing'] && $onTagStack) {
                    do {
                        $node = $node->parent;
                    } while ($current['tag'] !== array_pop($tags));
                }
            } elseif ('quote' === $current['tag']) {
                if (!$current['closing'] && !$onTagStack) {
                    $node = $addNode($root, Node::TYPE_BLOCK)->setTag($current['tag'])->setValue($current['attr']);
                    $tags = [$current['tag']];
                } elseif ($current['closing'] && $onTagStack) {
                    $node = $node->parent;
                    $tags = [];
                }
            }

            $advance($current);
        }

        // Text after last token
        if ('' !== ($text = substr($input, $position))) {
            $addNode($root, Node::TYPE_TEXT)->setValue($text);
        }

        return $root;
    }

    /**
     * Compiles a node (tree) back into a string.
     */
    private function compile(Node $node): string
    {
        if (Node::TYPE_ROOT === $node->type) {
            return $this->subCompile($node->children);
        }

        if (Node::TYPE_BLOCK === $node->type) {
            if ('' === ($children = $this->subCompile($node->children))) {
                return '';
            }

            switch ($node->tag) {
                case 'b':
                    return sprintf('<strong>%s</strong>', $children);

                case 'i':
                    return sprintf('<em>%s</em>', $children);

                case 'u':
                    return sprintf('<span style="text-decoration: underline">%s</span>', $children);

                case 'quote':
                    if (null !== $node->value) {
                        return sprintf(
                            '<blockquote><p>%s</p>%s</blockquote>',
                            sprintf($GLOBALS['TL_LANG']['MSC']['com_quote'], StringUtil::specialchars($node->value, true)),
                            $children
                        );
                    }

                    return sprintf('<blockquote>%s</blockquote>', $children);

                case 'email':
                    $uri = $node->value ?: $node->getFirstChildValue() ?? '';
                    $title = empty($node->value) ? $uri : $children;

                    try {
                        if (Validator::isEmail($uri)) {
                            return sprintf('<a href="mailto:%s">%s</a>', StringUtil::specialchars(Idna::encodeEmail($uri), true), StringUtil::specialchars($title, true));
                        }
                    } catch (\InvalidArgumentException $e) {
                    }

                    return StringUtil::specialchars($title, true);

                case 'url':
                    $uri = $node->value ?: $node->getFirstChildValue() ?? '';
                    $title = empty($node->value) ? $uri : $children;

                    try {
                        if (Validator::isUrl($uri)) {
                            return sprintf('<a href="%s" rel="noopener noreferrer nofollow">%s</a>', StringUtil::specialchars(Idna::encodeUrl($uri), true), StringUtil::specialchars($title, true));
                        }
                    } catch (\InvalidArgumentException $e) {
                    }

                    return StringUtil::specialchars($title, true);

                default:
                    throw new \RuntimeException('Invalid block value.');
            }
        }

        if (Node::TYPE_CODE === $node->type) {
            return sprintf('<div class="code"><p>%s</p><pre>%s</pre></div>', $GLOBALS['TL_LANG']['MSC']['com_code'], StringUtil::specialchars($node->value, true));
        }

        if (Node::TYPE_TEXT === $node->type) {
            return StringUtil::specialchars($node->value, true);
        }

        throw new \RuntimeException('Invalid node type.');
    }

    private function subCompile(array $nodes): string
    {
        return implode('', array_map(fn (Node $node): string => $this->compile($node), $nodes));
    }
}

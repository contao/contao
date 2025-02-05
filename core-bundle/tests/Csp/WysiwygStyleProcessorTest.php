<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Csp;

use Contao\CoreBundle\Csp\WysiwygStyleProcessor;
use PHPUnit\Framework\TestCase;

class WysiwygStyleProcessorTest extends TestCase
{
    /**
     * @dataProvider extractStylesProvider
     */
    public function testProcessStyles(string $html, array $expectedStyles, array $allowedCssProperties): void
    {
        $processor = new WysiwygStyleProcessor($allowedCssProperties);
        $this->assertSame($expectedStyles, $processor->extractStyles($html));
    }

    public static function extractStylesProvider(): iterable
    {
        yield 'HTML without styles' => [
            '<p data-foobar="true">Content</p>',
            [],
            [],
        ];

        yield 'One matching property' => [
            '<p style="text-decoration: underline">Content</p>',
            ['text-decoration: underline'],
            ['text-decoration' => 'underline'],
        ];

        yield 'Multiple matching properties' => [
            '<p style="text-decoration: underline; font-size: 8pt">Content</p>',
            ['text-decoration: underline; font-size: 8pt'],
            ['text-decoration' => 'underline', 'font-size' => '(8|10|12|14|18|24|36)pt'],
        ];

        yield 'Multiple styles when matching multiple properties' => [
            '<p style="text-decoration: underline; font-size: 8pt">Content<span style="color: red">I am red</span></p>',
            ['text-decoration: underline; font-size: 8pt', 'color: red'],
            ['text-decoration' => 'underline', 'font-size' => '(8|10|12|14|18|24|36)pt', 'color' => 'red'],
        ];

        yield 'Partial property allow list match should not extract the style (property not mentioned)' => [
            '<p style="text-decoration: underline; font-size: 8pt">Content</p>',
            [],
            ['text-decoration' => 'underline'],
        ];

        yield 'Partial property allow list match should not extract the style (regex does not match)' => [
            '<p style="text-decoration: underline; font-size: 8pt">Content</p>',
            [],
            ['text-decoration' => 'underline', 'font-size' => '(18|24|36)pt'],
        ];

        yield 'Empty property with value should not be be extracted' => [
            '<p style=":bad-value">Content</p>',
            [],
            ['text-decoration' => 'underline'],
        ];

        yield 'Empty property with empty value should be ignored' => [
            '<p style=" : ; text-decoration : underline ; ; ; ">Content</p>',
            [' : ; text-decoration : underline ; ; ; '],
            ['text-decoration' => 'underline'],
        ];

        yield 'Regex should only match with the whole value string' => [
            '<p style="text-decoration: underline">Content</p>',
            [],
            ['text-decoration' => 'nderlin|nderline|underlin'],
        ];

        yield 'Default config should match all properties correctly' => [
            <<<'EOF'
                    <p style="
                        text-align: left;
                        text-align: center;
                        text-align: right;
                        text-decoration: underline;
                        background-color: rgb(255, 0, 0);
                        background-color: #ff0000;
                        background-color: #FF0000;
                        color: rgb(0,255,0);
                        color: #00ff00;
                        color: #00FF00;
                        font-family: serif;
                        font-family: sans-serif;
                        font-family: &#039;Comic Sans MS&#039;, Georgia, sans-serif;
                        font-family: Comic Sans MS, sans-serif;
                        font-family: monospace;
                        font-family: system-ui;
                        font-size: 8pt;
                        font-size: 14pt;
                        font-size: 36pt;
                        line-height: 0;
                        line-height: 1;
                        line-height: 2.33333;
                        padding-left: 10px;
                        padding-left: 120px;
                        border-collapse: collapse;
                        margin-right: 0px;
                        margin-left: auto;
                        border-color: #00f;
                        vertical-align: middle;
                        vertical-align&#x3A;&#x20;bottom&#x3b;
                    ">Content</p>
                EOF,
            [
                <<<'EOF'

                            text-align: left;
                            text-align: center;
                            text-align: right;
                            text-decoration: underline;
                            background-color: rgb(255, 0, 0);
                            background-color: #ff0000;
                            background-color: #FF0000;
                            color: rgb(0,255,0);
                            color: #00ff00;
                            color: #00FF00;
                            font-family: serif;
                            font-family: sans-serif;
                            font-family: 'Comic Sans MS', Georgia, sans-serif;
                            font-family: Comic Sans MS, sans-serif;
                            font-family: monospace;
                            font-family: system-ui;
                            font-size: 8pt;
                            font-size: 14pt;
                            font-size: 36pt;
                            line-height: 0;
                            line-height: 1;
                            line-height: 2.33333;
                            padding-left: 10px;
                            padding-left: 120px;
                            border-collapse: collapse;
                            margin-right: 0px;
                            margin-left: auto;
                            border-color: #00f;
                            vertical-align: middle;
                            vertical-align: bottom;

                    EOF
                .'    ',
            ],
            [
                'text-align' => 'left|center|right|justify',
                'text-decoration' => 'underline',
                'background-color' => 'rgb\(\d{1,3},\s?\d{1,3},\s?\d{1,3}\)|#([0-9a-f]{3}){1,2}',
                'color' => 'rgb\(\d{1,3},\s?\d{1,3},\s?\d{1,3}\)|#([0-9a-f]{3}){1,2}',
                'font-family' => '((\'[a-z0-9 _-]+\'|[a-z0-9 _-]+)(,\s*|$))+',
                'font-size' => '[0-3]?\dpt',
                'line-height' => '[0-3](\.\d+)?',
                'padding-left' => '\d{1,3}px',
                'border-collapse' => 'collapse',
                'margin-right' => '0px|auto',
                'margin-left' => '0px|auto',
                'border-color' => 'rgb\(\d{1,3},\s?\d{1,3},\s?\d{1,3}\)|#([0-9a-f]{3}){1,2}',
                'vertical-align' => 'top|middle|bottom',
            ],
        ];
    }
}

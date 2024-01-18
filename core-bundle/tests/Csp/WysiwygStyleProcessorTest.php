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

    public function extractStylesProvider(): \Generator
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
    }
}

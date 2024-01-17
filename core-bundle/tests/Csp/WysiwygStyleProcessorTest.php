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
            ['text-decoration'],
        ];

        yield 'Multiple matching properties' => [
            '<p style="text-decoration: underline; font-size: 1.5rem">Content</p>',
            ['text-decoration: underline; font-size: 1.5rem'],
            ['text-decoration', 'font-size'],
        ];

        yield 'Multiple styles when matching multiple properties' => [
            '<p style="text-decoration: underline; font-size: 1.5rem">Content<span style="color: red">I am red</span></p>',
            ['text-decoration: underline; font-size: 1.5rem', 'color: red'],
            ['text-decoration', 'font-size', 'color'],
        ];

        yield 'Partial property allow list match should not extract the style' => [
            '<p style="text-decoration: underline; font-size: 1.5rem">Content</p>',
            [],
            ['text-decoration'],
        ];
    }
}

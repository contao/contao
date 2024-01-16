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

use Contao\CoreBundle\Csp\RandomClassGenerator\RandomClassGeneratorInterface;
use Contao\CoreBundle\Csp\WysiwygProcessor;
use PHPUnit\Framework\TestCase;

class WysiwygProcessorTest extends TestCase
{
    /**
     * @dataProvider processStylesProvider
     */
    public function testProcessStyles(string $html, string $expectedHtml, array $randomClasses = []): void
    {
        $randomClassGenerator = $this->createMock(RandomClassGeneratorInterface::class);
        $randomClassGenerator
            ->expects($this->exactly(\count($randomClasses)))
            ->method('getRandomClass')
            ->willReturnOnConsecutiveCalls(...$randomClasses)
        ;

        $processor = new WysiwygProcessor($randomClassGenerator);
        $this->assertSame($expectedHtml, $processor->processStyles($html, '39189c18'));
    }

    public function processStylesProvider(): \Generator
    {
        yield 'HTML without styles' => [
            '<p data-foobar="true">Content</p>',
            '<p data-foobar="true">Content</p>',
        ];

        yield 'Without nested tags' => [
            '<p style="text-decoration: underline">Content</p>',
            '<p class="csp-inline-style-380c0bd9ca2b3793">Content</p><style nonce="39189c18">.csp-inline-style-380c0bd9ca2b3793 { text-decoration: underline }</style>',
            [
                'csp-inline-style-380c0bd9ca2b3793',
            ],
        ];

        yield 'With nested tags' => [
            '<div style="font-weight: bold"><p style="text-decoration: underline"</p></div>',
            '<div class="csp-inline-style-a37292d22ed9eb6e"><p class="csp-inline-style-380c0bd9ca2b3793"></p></div><style nonce="39189c18">.csp-inline-style-380c0bd9ca2b3793 { text-decoration: underline }
.csp-inline-style-a37292d22ed9eb6e { font-weight: bold }</style>',
            [
                'csp-inline-style-380c0bd9ca2b3793',
                'csp-inline-style-a37292d22ed9eb6e',
            ],
        ];
    }
}

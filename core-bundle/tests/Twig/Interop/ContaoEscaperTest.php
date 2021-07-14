<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Interop;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContaoEscaper;
use Twig\Environment;
use Twig\Error\RuntimeError;

class ContaoEscaperTest extends TestCase
{
    /**
     * @dataProvider provideInput
     */
    public function testEscapesStrings($input, string $expectedOutput): void
    {
        $this->assertSame(
            $expectedOutput,
            $this->invokeContaoEscaper($input, null),
            'no charset specified'
        );

        $this->assertSame(
            $expectedOutput,
            $this->invokeContaoEscaper($input, 'UTF-8'),
            'UTF-8'
        );

        $this->assertSame(
            $expectedOutput,
            $this->invokeContaoEscaper($input, 'utf-8'),
            'utf-8'
        );
    }

    public function provideInput(): \Generator
    {
        yield 'simple string' => [
            'foo',
            'foo',
        ];

        yield 'integer' => [
            123,
            '123',
        ];

        yield 'string with entities' => [
            'A & B &rarr; &#9829;',
            'A &amp; B &rarr; &#9829;',
        ];

        yield 'string with uppercase entities' => [
            '&AMP; &QUOT; &LT; &GT;',
            '&amp; &quot; &lt; &gt;',
        ];
    }

    public function testThrowsErrorIfCharsetIsNotUtf8(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The "contao_html" escape filter does not support the ISO-8859-1 charset, use UTF-8 instead.');

        $this->invokeContaoEscaper('foo', 'ISO-8859-1');
    }

    private function invokeContaoEscaper($input, ?string $charset): string
    {
        return (new ContaoEscaper())($this->createMock(Environment::class), $input, $charset);
    }
}

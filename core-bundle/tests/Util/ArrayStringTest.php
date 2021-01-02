<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Util;

use Contao\CoreBundle\Exception\ArrayStringParserException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\ArrayString;

class ArrayStringTest extends TestCase
{
    /**
     * @dataProvider provideValidStrings
     */
    public function testConvertsStringToArray(string $input, array $expected): void
    {
        $this->assertSame($expected, ArrayString::parse($input));
    }

    public function provideValidStrings(): \Generator
    {
        yield 'empty array' => [
            '[]',
            [],
        ];

        yield 'simple array' => [
            '[a, b,c]',
            ['a', 'b', 'c'],
        ];

        yield 'auto detected types' => [
            '[true, false, 123, -4.5, null, something else]',
            [true, false, 123, -4.5, null, 'something else'],
        ];

        yield 'strings can contain any character' => [
            '["true", \'false\', \'any ,."[character]"\', "\'", \'"\', " "]',
            ['true', 'false', 'any ,."[character]"', '\'', '"', ' '],
        ];

        yield 'associative arrays' => [
            '[a: b, 0: foo, true: bar]',
            ['a' => 'b', '0' => 'foo', 'true' => 'bar'],
        ];

        yield 'mixed arrays' => [
            '[a, foo:bar, c]',
            [0 => 'a', 2 => 'c', 'foo' => 'bar'],
        ];

        yield 'nested arrays' => [
            '[a: [x, 1], [foo: [bar: baz]] ]',
            [
                1 => [
                    'foo' => [
                        'bar' => 'baz',
                    ],
                ],
                'a' => ['x', 1],
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidStrings
     */
    public function testThrowsIfFormatIsInvalid(string $input, string $exception): void
    {
        $this->expectException(ArrayStringParserException::class);
        $this->expectDeprecationMessage($exception);

        ArrayString::parse($input);
    }

    public function provideInvalidStrings(): \Generator
    {
        yield 'missing brackets' => [
            'a, b',
            'Bad format: Array notation must include outer brackets.',
        ];

        yield 'empty key' => [
            '[a,:b]',
            'Bad format: Key for index 1 cannot be empty.',
        ];

        yield 'empty value' => [
            '[a,b:]',
            "Bad format: Key 'b' is missing a value.",
        ];

        yield 'missing elements' => [
            '[a,,]',
            "Bad format: Did not expect to see ',' at position 2.",
        ];

        yield 'duplicate keys' => [
            '[a:1,b:2,a:3]',
            "Bad format: Key 'a' cannot appear more than once.",
        ];
    }
}

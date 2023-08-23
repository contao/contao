<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\ArrayUtil;
use Contao\CoreBundle\Tests\TestCase;

class ArrayUtilTest extends TestCase
{
    /**
     * @dataProvider sortByOrderFieldProvider
     */
    public function testSortsByOrderField(array $items, array $order, array $expected): void
    {
        $this->assertSame($expected, ArrayUtil::sortByOrderField($items, $order));

        $itemArrays = array_map(static fn ($item): array => ['uuid' => $item], $items);
        $expectedArrays = array_map(static fn ($item): array => ['uuid' => $item], $expected);

        $this->assertSame($expectedArrays, ArrayUtil::sortByOrderField($itemArrays, $order));
        $this->assertSame($expectedArrays, ArrayUtil::sortByOrderField($itemArrays, serialize($order)));

        $itemArrays = array_map(static fn ($item): array => ['id' => $item], $items);
        $expectedArrays = array_map(static fn ($item): array => ['id' => $item], $expected);

        $this->assertSame($expectedArrays, ArrayUtil::sortByOrderField($itemArrays, $order, 'id'));
        $this->assertSame($expectedArrays, ArrayUtil::sortByOrderField($itemArrays, serialize($order), 'id'));

        $itemObjects = array_map(static fn ($item): \stdClass => (object) ['uuid' => $item], $items);
        $expectedObjects = array_map(static fn ($item): \stdClass => (object) ['uuid' => $item], $expected);

        $this->assertSame(array_map('get_object_vars', $expectedObjects), array_map('get_object_vars', ArrayUtil::sortByOrderField($itemObjects, $order)));
        $this->assertSame(array_map('get_object_vars', $expectedObjects), array_map('get_object_vars', ArrayUtil::sortByOrderField($itemObjects, serialize($order))));

        $itemObjects = array_map(static fn ($item): \stdClass => (object) ['id' => $item], $items);
        $expectedObjects = array_map(static fn ($item): \stdClass => (object) ['id' => $item], $expected);

        $this->assertSame(array_map('get_object_vars', $expectedObjects), array_map('get_object_vars', ArrayUtil::sortByOrderField($itemObjects, $order, 'id')));
        $this->assertSame(array_map('get_object_vars', $expectedObjects), array_map('get_object_vars', ArrayUtil::sortByOrderField($itemObjects, serialize($order), 'id')));

        $itemFlipped = array_map(static fn () => 'X', array_flip($items));
        $expectedFlipped = array_map(static fn () => 'X', array_flip($expected));

        $this->assertSame($expectedFlipped, ArrayUtil::sortByOrderField($itemFlipped, $order, null, true));
        $this->assertSame($expectedFlipped, ArrayUtil::sortByOrderField($itemFlipped, serialize($order), null, true));
    }

    public function sortByOrderFieldProvider(): \Generator
    {
        yield [
            ['a', 'b', 'c'],
            [],
            ['a', 'b', 'c'],
        ];

        yield [
            ['a', 'b', 'c'],
            ['b', 'c', 'a'],
            ['b', 'c', 'a'],
        ];

        yield [
            ['a', 'b', 'c'],
            ['b'],
            ['b', 'a', 'c'],
        ];

        yield [
            ['a', 'b', 'c'],
            ['X'],
            ['a', 'b', 'c'],
        ];

        yield [
            [0, 1, 2],
            [],
            [0, 1, 2],
        ];

        yield [
            [0, 1, 2],
            [1, 2, 0],
            [1, 2, 0],
        ];

        yield [
            [0, 1, 2],
            [1],
            [1, 0, 2],
        ];

        yield [
            [0, 1, 2],
            [99],
            [0, 1, 2],
        ];
    }

    public function testRecursiveKeySort(): void
    {
        $unsorted = [
            'foo' => 'bar',
            '@type' => 'foo',
            '@id' => 'foo',
            'nested' => [
                'foo' => 'bar',
                '@foo' => 'foo',
                'bar' => [
                    'baz' => 'bar',
                    'ab' => 'yz',
                ],
            ],
        ];

        ArrayUtil::recursiveKeySort($unsorted);

        $this->assertSame(
            [
                '@id' => 'foo',
                '@type' => 'foo',
                'foo' => 'bar',
                'nested' => [
                    '@foo' => 'foo',
                    'bar' => [
                        'ab' => 'yz',
                        'baz' => 'bar',
                    ],
                    'foo' => 'bar',
                ],
            ],
            $unsorted,
        );
    }
}

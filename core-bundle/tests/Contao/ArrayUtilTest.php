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

    /**
     * @param mixed $expected
     * @param string|array $path
     *
     * @dataProvider getDataProvider
     */
    public function testGetsDataViaDotNotation(array $data, array|string $path, mixed $expected): void
    {
        $this->assertSame($expected, ArrayUtil::get($data, $path));
    }

    public function getDataProvider(): \Generator
    {
        $source = [
            'foo' => 'bar',
            'bar' => ['foo' => 'bar'],
            'baz' => ['foo' => ['bar' => 'baz']],
            'foo.bar' => 'baz',
            'foo\bar' => 'foo',
        ];

        yield 'simple key' => [$source, 'foo', 'bar'];
        yield 'dot notation path' => [$source, 'foo.bar', null];
        yield 'array path' => [$source, ['foo', 'bar'], null];
        yield 'non-scalar result' => [$source, 'bar', ['foo' => 'bar']];
        yield 'deep dot notation path' => [$source, 'baz.foo.bar', 'baz'];
        yield 'dot notation with escaped dot' => [$source, 'foo\.bar', 'baz'];
        yield 'dot notation with escaped slash' => [$source, 'foo\\bar', 'foo'];
    }

    public function testReturnsProvidedDefaultForNullDataOnGet(): void
    {
        $source = [
            'foo' => null,
            'bar.baz' => null,
            'falsy' => [
                'bool' => false,
                'int' => 0,
                'string' => '',
            ],
        ];

        $this->assertSame('default', ArrayUtil::get($source, 'foo', 'default'));
        $this->assertSame('default', ArrayUtil::get($source, 'bar.baz', 'default'));
        $this->assertFalse(ArrayUtil::get($source, 'falsy.bool', 'default'));
        $this->assertSame(0, ArrayUtil::get($source, 'falsy.int', 'default'));
        $this->assertSame('', ArrayUtil::get($source, 'falsy.string', 'default'));
        $this->assertSame('default', ArrayUtil::get($source, 'invalid', 'default'));
    }

    public function testThrowsExceptionForInvalidPathIfConfigured(): void
    {
        $source = [
            'foo' => [],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('bar.baz');

        ArrayUtil::get($source, 'foo.bar.baz', null, true);
    }

    /**
     * @param string|array $path
     * @param mixed $value
     * @param mixed $expected
     *
     * @dataProvider setDataProvider
     */
    public function testSetsDataViaDotNotation(array $source, array|string $path, mixed $value, mixed $expected, bool $overwrite = true): void
    {
        $source = ArrayUtil::set($source, $path, $value, $overwrite);

        $this->assertSame($expected, ArrayUtil::get($source, $path));
    }

    public function setDataProvider(): \Generator
    {
        yield 'simple key' => [[], 'foo', 'bar', 'bar'];
        yield 'nested path' => [[], 'foo.bar.baz', 'foo', 'foo'];
        yield 'array path' => [[], ['foo', 'bar', 'baz'], 'foo', 'foo'];
        yield 'overwriting' => [['foo' => 'bar'], 'foo', 'baz', 'baz'];
        yield 'not overwriting' => [['foo' => 'bar'], 'foo', 'baz', 'bar', false];
        yield 'setting non-scalar' => [[], 'foo.bar', ['baz'], ['baz']];
        yield 'setting null' => [[], 'foo.bar', null, null];
    }

    /**
     * @dataProvider dotNotationProvider
     */
    public function testTransformsDotNotationToArray(string $path, array $expected): void
    {
        $this->assertSame($expected, ArrayUtil::pathToArray($path));
    }

    public function dotNotationProvider(): \Generator
    {
        yield ['foo', ['foo']];
        yield ['foo.bar', ['foo', 'bar']];
        yield ['foo\bar.baz', ['foo\bar', 'baz']];
        yield ['foo\.bar.baz', ['foo.bar', 'baz']];
        yield ['foo\\bar.baz', ['foo\bar', 'baz']];
    }
}

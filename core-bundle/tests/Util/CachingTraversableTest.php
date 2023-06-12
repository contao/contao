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

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\CachingTraversable;

class CachingTraversableTest extends TestCase
{
    /**
     * @dataProvider provideItems
     *
     * @param list<array{0:mixed, 1:mixed}> $items
     */
    public function testIterateMultipleTimes(array $items): void
    {
        $cachingTraversable = new CachingTraversable($this->generateItems($items, $generatorLog));

        $consumedItems = $this->consumeItems($cachingTraversable, 2);
        $expectedItems = \array_slice($items, 0, 2);

        $this->assertSame($expectedItems, $consumedItems);
        $this->assertGeneratedItems(2, $generatorLog);

        // Should use cache
        $this->assertSame($expectedItems, $this->consumeItems($cachingTraversable, 2));
        $this->assertGeneratedItems(2, $generatorLog);

        // Should continue iterating
        $this->assertSame(\array_slice($items, 0, 4), $this->consumeItems($cachingTraversable, 4));
        $this->assertGeneratedItems(4, $generatorLog);
    }

    public function provideItems(): \Generator
    {
        yield 'integer keys' => [[
            [0, 'A'],
            [1, 'B'],
            [2, 'D'],
            [3, 'E'],
        ]];

        yield 'object keys' => [[
            [new \stdClass(), 'A'],
            [new \stdClass(), 'B'],
            [new \stdClass(), 'D'],
            [new \stdClass(), 'E'],
        ]];
    }

    public function testIterateAndConsumeInParallel(): void
    {
        $items = [
            ['I', 1],
            ['II', 2],
            ['III', 3],
        ];

        $cachingTraversable = new CachingTraversable($this->generateItems($items, $generatorLog));

        $multipleIterator = new \MultipleIterator();
        $multipleIterator->attachIterator($cachingTraversable->getIterator());
        $multipleIterator->attachIterator($cachingTraversable->getIterator());

        $consumedItems = $this->consumeItems($multipleIterator);

        $this->assertSame(
            [
                [['I', 'I'], [1, 1]],
                [['II', 'II'], [2, 2]],
                [['III', 'III'], [3, 3]],
            ],
            $consumedItems
        );

        $this->assertGeneratedItems(3, $generatorLog);
    }

    public function testIterateEmptyGenerator(): void
    {
        /** @var list<array{mixed, mixed}> $items */
        $items = [];
        $cachingTraversable = new CachingTraversable($this->generateItems($items, $generatorLog));

        $this->assertEmpty($this->consumeItems($cachingTraversable));
        $this->assertEmpty($this->consumeItems($cachingTraversable));

        $this->assertGeneratedItems(0, $generatorLog);
    }

    public function testConsumeAll(): void
    {
        $items = [[0, new \stdClass()], [1, new \stdClass()]];
        $cachingTraversable = new CachingTraversable($this->generateItems($items, $generatorLog));

        $this->assertSame($items, $this->consumeItems($cachingTraversable));
        $this->assertSame($items, $this->consumeItems($cachingTraversable));

        $this->assertGeneratedItems(2, $generatorLog);
    }

    public function testIterateSeekableIterator(): void
    {
        $arrayIterator = new \ArrayIterator(['a' => 'foo', 'b' => 'bar']);
        $cachingTraversable = new CachingTraversable($arrayIterator);

        $this->assertSame($this->consumeItems($arrayIterator), $this->consumeItems($cachingTraversable));

        // Should behave identical when consumed again
        $this->assertSame($this->consumeItems($arrayIterator), $this->consumeItems($cachingTraversable));
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @param list<array{0:TKey, 1:TValue}> $items
     *
     * @param-out list<int> $generatorLog
     *
     * @return \Generator<TKey, TValue>
     */
    private function generateItems(array $items, array|null &$generatorLog = null): \Generator
    {
        $generatorLog = [];

        foreach ($items as $i => $item) {
            $generatorLog[] = $i;
            yield $item[0] => $item[1];
        }
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @param \Traversable<TKey, TValue> $items
     *
     * @return list<array{0:TKey, 1:TValue}>
     */
    private function consumeItems(\Traversable $items, int $limit = PHP_INT_MAX): array
    {
        $consumed = [];

        foreach ($items as $key => $value) {
            $consumed[] = [$key, $value];

            if (1 === $limit--) {
                break;
            }
        }

        return $consumed;
    }

    private function assertGeneratedItems(int $expectedItems, array $generatorLog): void
    {
        if (0 === $expectedItems) {
            $this->assertEmpty($generatorLog);

            return;
        }

        $this->assertSame(range(0, $expectedItems - 1), $generatorLog);
    }
}

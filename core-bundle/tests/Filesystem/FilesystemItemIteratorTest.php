<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem;

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Tests\TestCase;

class FilesystemItemIteratorTest extends TestCase
{
    public function testFilterAndIterate(): void
    {
        $iterator = new FilesystemItemIterator([
            new FilesystemItem(true, 'foo.jpg'),
            new FilesystemItem(false, 'bar'),
            new FilesystemItem(true, 'baz.txt'),
            new FilesystemItem(false, 'foobar'),
        ]);

        $allItems = [];

        foreach ($iterator as $item) {
            $allItems[] = $item;
        }

        $this->assertSameItems(['foo.jpg', 'bar', 'baz.txt', 'foobar'], $allItems);
        $this->assertSameItems(['foo.jpg', 'bar', 'baz.txt', 'foobar'], $iterator->toArray());

        $files = [];

        foreach ($iterator->files() as $item) {
            $files[] = $item;
        }

        $this->assertSameItems(['foo.jpg', 'baz.txt'], $files);
        $this->assertSameItems(['foo.jpg', 'baz.txt'], $iterator->files()->toArray());

        $directories = [];

        foreach ($iterator->directories() as $item) {
            $directories[] = $item;
        }

        $this->assertSameItems(['bar', 'foobar'], $directories);
        $this->assertSameItems(['bar', 'foobar'], $iterator->directories()->toArray());

        $custom = [];
        $customFilter = static fn (FilesystemItem $item) => 'b' === $item->getPath()[0];

        foreach ($iterator->filter($customFilter) as $item) {
            $custom[] = $item;
        }

        $this->assertSameItems(['bar', 'baz.txt'], $custom);
        $this->assertSameItems(['bar', 'baz.txt'], $iterator->filter($customFilter)->toArray());
    }

    /**
     * @dataProvider provideInvalidItems
     *
     * @param mixed $item
     */
    public function testEnsuresTypeSafetyWhenIterating($item, string $expectedType): void
    {
        $iterator = new FilesystemItemIterator([$item]);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Contao\CoreBundle\Filesystem\FilesystemItemIterator can only iterate over elements of type Contao\CoreBundle\Filesystem\FilesystemItem, got '.$expectedType);

        iterator_to_array($iterator);
    }

    public function provideInvalidItems(): \Generator
    {
        yield 'scalar' => [42, 'integer'];
        yield 'object of wrong type' => [new \stdClass(), 'stdClass'];
    }

    public function testIterateMultipleTimesWithGenerator(): void
    {
        $iterator = new FilesystemItemIterator($this->generateItems());

        $this->assertSameItems(['foo', 'bar'], iterator_to_array($iterator));
        $this->assertSameItems(['foo', 'bar'], iterator_to_array($iterator));
    }

    /**
     * @param array<string>         $expected
     * @param array<FilesystemItem> $actual
     */
    private function assertSameItems(array $expected, array $actual): void
    {
        $this->assertSame($expected, array_map(static fn (FilesystemItem $item): string => $item->getPath(), $actual));
    }

    private function generateItems(): \Generator
    {
        yield new FilesystemItem(true, 'foo');
        yield new FilesystemItem(true, 'bar');
    }
}

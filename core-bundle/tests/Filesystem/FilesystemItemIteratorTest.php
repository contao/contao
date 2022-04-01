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

    public function testSort(): void
    {
        $fileA = new FilesystemItem(true, 'foo/a', 100);
        $fileB = new FilesystemItem(true, 'foo/b', 200);
        $fileC = new FilesystemItem(true, 'bar/a', 300);
        $fileD = new FilesystemItem(true, 'bar/b', 400);
        $dirFoo = new FilesystemItem(false, 'foo', null);
        $dirBar = new FilesystemItem(false, 'bar', 500);

        $iterator = new FilesystemItemIterator([$fileA, $fileB, $fileC, $fileD, $dirFoo, $dirBar]);

        $sortedByPathAsc = $iterator->sort(FilesystemItemIterator::SORT_BY_PATH_ASC);
        $sortedByPathDesc = $sortedByPathAsc->sort(FilesystemItemIterator::SORT_BY_PATH_DESC);
        $sortedByDateAsc = $sortedByPathDesc->sort(FilesystemItemIterator::SORT_BY_LAST_MODIFIED_ASC);
        $sortedByDateDesc = $sortedByDateAsc->sort(FilesystemItemIterator::SORT_BY_LAST_MODIFIED_DESC);

        $expectedByPath = [$dirBar, $fileC, $fileD, $dirFoo, $fileA, $fileB];

        $this->assertSame($expectedByPath, iterator_to_array($sortedByPathAsc));
        $this->assertSame(array_reverse($expectedByPath), iterator_to_array($sortedByPathDesc));

        $expectedByDate = [$dirFoo, $fileA, $fileB, $fileC, $fileD, $dirBar];

        $this->assertSame($expectedByDate, iterator_to_array($sortedByDateAsc));
        $this->assertSame(array_reverse($expectedByDate), iterator_to_array($sortedByDateDesc));
    }

    public function testThrowsIfSortingModeIsNotSupported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported sorting mode "foobar", must be one of "name_asc", "name_desc", "date_asc", "date_desc".');

        (new FilesystemItemIterator([]))->sort('foobar');
    }

    public function testAny(): void
    {
        $iterator = new FilesystemItemIterator([
            new FilesystemItem(true, 'foo.jpg'),
            new FilesystemItem(false, 'bar'),
            new FilesystemItem(true, 'baz.txt'),
            new FilesystemItem(false, 'foobar'),
        ]);

        $this->assertTrue($iterator->any(static fn (FilesystemItem $f): bool => $f->isFile()));
        $this->assertTrue($iterator->any(static fn (FilesystemItem $f): bool => str_starts_with($f->getPath(), 'ba')));
        $this->assertFalse($iterator->any(static fn (FilesystemItem $f): bool => str_starts_with($f->getPath(), 'x')));
    }

    public function testAll(): void
    {
        $iterator = new FilesystemItemIterator([
            new FilesystemItem(true, 'foo.jpg'),
            new FilesystemItem(true, 'foo.csv'),
            new FilesystemItem(true, 'baz.txt'),
            new FilesystemItem(true, 'foo_bar'),
        ]);

        $this->assertTrue($iterator->all(static fn (FilesystemItem $f): bool => $f->isFile()));
        $this->assertTrue($iterator->all(static fn (FilesystemItem $f): bool => 7 === \strlen($f->getPath())));
        $this->assertFalse($iterator->all(static fn (FilesystemItem $f): bool => str_starts_with($f->getPath(), 'foo')));
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

    /**
     * @param array<string>         $expected
     * @param array<FilesystemItem> $actual
     */
    private function assertSameItems(array $expected, array $actual): void
    {
        $this->assertSame($expected, array_map(static fn (FilesystemItem $item): string => $item->getPath(), $actual));
    }
}

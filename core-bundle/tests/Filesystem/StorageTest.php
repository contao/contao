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

use Contao\CoreBundle\Filesystem\Storage;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;

class StorageTest extends TestCase
{
    public function testListPathsContainsResources(): void
    {
        $storage = new Storage($this->getTestFilesystem());
        $paths = $storage->listSynchronizablePaths();

        // must be included
        $this->assertContains('foo.txt', $paths);
        $this->assertContains('folderA/foobar', $paths);
        $this->assertContains('folderA/test/', $paths);
        $this->assertContains('folderA/test/a', $paths);
        $this->assertContains('folderA/test/b/', $paths);

        // must NOT be excluded
        $this->assertContains('ignored/', $paths);
        $this->assertContains('folderB/.nosync/', $paths);
        $this->assertContains('folderB/.nosync/test1', $paths);
    }

    public function testListPathsDoesNotContainExcludedResources(): void
    {
        $storage = new Storage($this->getTestFilesystem());
        $paths = $storage->listSynchronizablePaths();

        $this->assertNotContains('ignored/.nosync', $paths);
        $this->assertNotContains('ignored/baz1', $paths);
        $this->assertNotContains('ignored/baz/', $paths);
        $this->assertNotContains('ignored/baz/x', $paths);
    }

    public function testListSkipsLargeFiles(): void
    {
        $filesystem = new Filesystem(new MemoryAdapter());
        $filesystem->write('small.file', '');
        $filesystem->write('large.file', implode('', array_fill(0, 2097153, 'A'))); // 2GB + 1

        $storage = new Storage($filesystem);
        $paths = $storage->listSynchronizablePaths();

        $this->assertContains('small.file', $paths);
        $this->assertNotContains('large.file', $paths);
    }

    public function testListEnforcesListingOrder(): void
    {
        $filesystem = new Filesystem(new MemoryAdapter());
        $filesystem->write('a/z/foo/bar', '');

        $storage = new Storage($filesystem);
        $paths = iterator_to_array($storage->listSynchronizablePaths());

        // most specific first
        $this->assertSame(['a/z/foo/bar', 'a/z/foo/', 'a/z/', 'a/'], $paths);
    }

    /**
     * @testWith ["folderA"]
     *           ["folderA/"]
     *           ["folderA/foobar"]
     */
    public function testExcludeFromSync($resource): void
    {
        $filesystem = $this->getTestFilesystem();
        $storage = new Storage($filesystem);

        $this->assertFalse($filesystem->has('folderA/.nosync'));
        $this->assertContains('folderA/foobar', $storage->listSynchronizablePaths());

        $storage->excludeFromSync($resource);

        $this->assertTrue($filesystem->has('folderA/.nosync'));
        $this->assertNotContains('folderA/foobar', $storage->listSynchronizablePaths());
    }

    public function testExcludeFromSyncFailsWithBadResource(): void
    {
        $this->expectException(FileNotFoundException::class);

        $storage = new Storage($this->getTestFilesystem());
        $storage->excludeFromSync('bad-file');
    }

    public function testExcludeFromSyncFailsIfAlreadyExcluded(): void
    {
        $this->expectExceptionMessage("Resource is already explicitly excluded from sync. See: 'ignored/.nosync'");

        $storage = new Storage($this->getTestFilesystem());
        $storage->excludeFromSync('ignored');
    }

    /**
     * @testWith ["ignored"]
     *           ["ignored/"]
     *           ["ignored/.nosync"]
     */
    public function testIncludeToSync($resource): void
    {
        $filesystem = $this->getTestFilesystem();
        $storage = new Storage($filesystem);

        $this->assertTrue($filesystem->has('ignored/.nosync'));
        $this->assertNotContains('ignored/baz1', $storage->listSynchronizablePaths());
        $this->assertNotContains('ignored/baz/x', $storage->listSynchronizablePaths());

        $storage->includeToSync($resource);

        $this->assertFalse($filesystem->has('ignored/.nosync'));
        $this->assertContains('ignored/baz1', $storage->listSynchronizablePaths());
        $this->assertContains('ignored/baz/x', $storage->listSynchronizablePaths());
    }

    public function testIncludeToSyncFailsWithBadResource(): void
    {
        $this->expectException(FileNotFoundException::class);

        $storage = new Storage($this->getTestFilesystem());
        $storage->includeToSync('bad-file');
    }

    public function testIncludeToSyncFailsIfExclusionIsInherited(): void
    {
        $this->expectExceptionMessage("The sync exclusion of 'ignored/baz/x' is inherited and therefore cannot be removed.");

        $storage = new Storage($this->getTestFilesystem());
        $storage->includeToSync('ignored/baz/x');
    }

    public function testIncludeToSyncFailsIfNotExcluded(): void
    {
        $this->expectExceptionMessage("The resource 'folderA' is not excluded from sync.");

        $storage = new Storage($this->getTestFilesystem());
        $storage->includeToSync('folderA');
    }

    public function testIsExcludedFromSync(): void
    {
        $storage = new Storage($this->getTestFilesystem());

        $this->assertTrue($storage->isExcludedFromSync('ignored/baz1'));
        $this->assertTrue($storage->isExcludedFromSync('ignored/baz/x'));
        $this->assertTrue($storage->isExcludedFromSync('ignored/.nosync'));

        $this->assertFalse($storage->isExcludedFromSync('foo.txt'));
        $this->assertFalse($storage->isExcludedFromSync('folderA/test/'));
        $this->assertFalse($storage->isExcludedFromSync('folderA/test/a'));
    }

    public function testIsExcludedFailsWithBadResource(): void
    {
        $this->expectException(FileNotFoundException::class);

        $storage = new Storage($this->getTestFilesystem());
        $storage->isExcludedFromSync('bad-file');
    }

    private function getTestFilesystem()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $filesystem->write('foo.txt', 'foo');
        $filesystem->write('bar.txt', 'bar');

        $filesystem->write('folderA/foobar', '123');
        $filesystem->write('folderA/empty_file', '');
        $filesystem->write('folderA/test/a', 'a');
        $filesystem->createDir('folderA/test/b');
        $filesystem->write('folderA/test/c', 'b');

        $filesystem->createDir('a/deeply/nested/path');

        $filesystem->write('ignored/.nosync', '');
        $filesystem->write('ignored/baz1', 'baz');
        $filesystem->write('ignored/baz2', 'baz');
        $filesystem->write('ignored/baz/x', 'x');

        $filesystem->write('folderB/.nosync/test1', 'test1');
        $filesystem->write('folderB/test2', 'test2');

        return $filesystem;
    }
}

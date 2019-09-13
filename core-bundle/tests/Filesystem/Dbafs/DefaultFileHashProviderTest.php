<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Dbafs;

use Contao\CoreBundle\Filesystem\Dbafs\DefaultFileHashProvider;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;

class DefaultFileHashProviderTest extends TestCase
{
    public function testGetHash(): void
    {
        $filesystem = new Filesystem(new MemoryAdapter());
        $hashProvider = new DefaultFileHashProvider($filesystem);

        $filesystem->write('foo/file1', 'abc');
        $filesystem->write('foo/file2', 'def');
        $filesystem->write('foo/some/file', '123456');
        $filesystem->write('bar.txt', '123456');
        $filesystem->write('baz.txt', '');
        $filesystem->createDir('valid/folder');

        $hashes = $hashProvider->getHashes([
            'foo/file1',
            'foo/file2',
            'foo/some/',
            'foo/some/file',
            'bar.txt',
            'valid/folder',
            'invalid/folder',
            'non/existent/resource.jpg',
        ]);
        ksort($hashes);

        $this->assertSame([
            'bar.txt' => 'e10adc3949ba59abbe56e057f20f883e',
            'foo/file1' => '900150983cd24fb0d6963f7d28e17f72',
            'foo/file2' => '4ed9407630eb1000c0f6b63842defa7d',
            'foo/some/' => null,
            'foo/some/file' => 'e10adc3949ba59abbe56e057f20f883e',
            'valid/folder' => null,
        ], $hashes);
    }
}

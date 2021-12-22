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
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class MountManagerTest extends TestCase
{
    public function testMountAdapters(): void
    {
        $manager = new MountManager($rootAdapter = new InMemoryFilesystemAdapter());

        $this->assertSame(
            [
                '' => $rootAdapter,
            ],
            $manager->getMounts(),
            'mounts root adapter by default'
        );

        $manager->mount($filesAdapter = new InMemoryFilesystemAdapter(), 'files');
        $manager->mount($mediaAdapter = new InMemoryFilesystemAdapter(), 'files/media');

        $this->assertSame(
            [
                'files/media' => $mediaAdapter,
                'files' => $filesAdapter,
                '' => $rootAdapter,
            ],
            $manager->getMounts(),
            'lists in descending specificity'
        );

        $manager->mount($newFilesAdapter = new InMemoryFilesystemAdapter(), 'files');

        $this->assertSame(
            [
                'files/media' => $mediaAdapter,
                'files' => $newFilesAdapter,
                '' => $rootAdapter,
            ],
            $manager->getMounts(),
            'allows overwriting existing mount points'
        );
    }

    /**
     * @dataProvider provideCalls
     */
    public function testDelegatesCallsToMostSpecificAdapter(array $call, array $expectedDelegateCall): void
    {
        [$delegateMethod, $delegateArguments, $delegateReturn] = $expectedDelegateCall;

        $rootAdapter = $this->mockFilesystemAdapterThatDoesNotReceiveACall($delegateMethod);
        $filesAdapter = $this->mockFilesystemAdapterThatDoesNotReceiveACall($delegateMethod);
        $filesMediaAdapter = $this->mockFilesystemAdapterWithCall($delegateMethod, ['foo', ...$delegateArguments], $delegateReturn);

        $manager = new MountManager($rootAdapter);
        $manager->mount($filesAdapter, 'files');
        $manager->mount($filesMediaAdapter, 'files/media');

        [$method, $arguments, $return] = $call;
        $this->assertSame($return, $manager->$method('files/media/foo', ...$arguments));

        $this->closeStreamResources($arguments);
    }

    /**
     * @dataProvider provideCalls
     */
    public function testFallsBackToRootAdapter(array $call, array $expectedDelegateCall): void
    {
        [$delegateMethod, $delegateArguments, $delegateReturn] = $expectedDelegateCall;

        $rootAdapter = $this->mockFilesystemAdapterWithCall($delegateMethod, ['some/place', ...$delegateArguments], $delegateReturn);
        $filesAdapter = $this->mockFilesystemAdapterThatDoesNotReceiveACall($delegateMethod);

        $manager = new MountManager($rootAdapter);
        $manager->mount($filesAdapter, 'files');

        [$method, $arguments, $return] = $call;

        $this->assertSame($return, $manager->$method('some/place', ...$arguments));

        $this->closeStreamResources($arguments);
    }

    /**
     * @dataProvider provideCalls
     */
    public function testWrapsFlysystemExceptionIntoVirtualFilesystemException(array $call, array $expectedDelegateCall): void
    {
        [$delegateMethod, ,] = $expectedDelegateCall;

        $flysystemException = new class() extends \RuntimeException implements FilesystemException {
        };

        $adapter = $this->createMock(FilesystemAdapter::class);
        $adapter
            ->method($delegateMethod)
            ->willThrowException($flysystemException)
        ;

        $manager = new MountManager($this->mockFilesystemAdapterThatDoesNotReceiveACall($delegateMethod));
        $manager->mount($adapter, 'some');

        [$method, $arguments,] = $call;

        try {
            $manager->$method('some/place', ...$arguments);
        } catch (VirtualFilesystemException $e) {
            $this->assertSame($flysystemException, $e->getPrevious());
            $this->assertSame('some/place', $e->getPath());
        }

        $this->closeStreamResources($arguments);
    }

    public function provideCalls(): \Generator
    {
        yield 'fileExists' => [
            [
                'fileExists',
                [],
                true,
            ],
            [
                'fileExists',
                [],
                true,
            ],
        ];

        yield 'read' => [
            [
                'read',
                [],
                'foo',
            ],
            [
                'read',
                [],
                'foo',
            ],
        ];

        $streamResource = tmpfile();

        yield 'readStream' => [
            [
                'readStream',
                [],
                $streamResource,
            ],
            [
                'readStream',
                [],
                $streamResource,
            ],
        ];

        yield 'write' => [
            [
                'write',
                ['contents', ['some_config' => 'some_value']],
                null,
            ],
            [
                'write',
                ['contents', new Config(['some_config' => 'some_value'])],
                null,
            ],
        ];

        yield 'writeStream' => [
            [
                'writeStream',
                [$streamResource, ['some_config' => 'some_value']],
                null,
            ],
            [
                'writeStream',
                [$streamResource, new Config(['some_config' => 'some_value'])],
                null,
            ],
        ];

        yield 'delete' => [
            [
                'delete',
                [],
                null,
            ],
            [
                'delete',
                [],
                null,
            ],
        ];

        yield 'delete directory' => [
            [
                'deleteDirectory',
                [],
                null,
            ],
            [
                'deleteDirectory',
                [],
                null,
            ],
        ];

        yield 'create directory' => [
            [
                'createDirectory',
                [['some_config' => 'some_value']],
                null,
            ],
            [
                'createDirectory',
                [new Config(['some_config' => 'some_value'])],
                null,
            ],
        ];

        $fileAttributes = new FileAttributes(
            'dummy',
            1024,
            null,
            123450,
            'application/x-empty',
        );

        yield 'last modified' => [
            [
                'getLastModified',
                [],
                123450,
            ],
            [
                'lastModified',
                [],
                $fileAttributes,
            ],
        ];

        yield 'file size' => [
            [
                'getFileSize',
                [],
                1024,
            ],
            [
                'fileSize',
                [],
                $fileAttributes,
            ],
        ];

        yield 'mime type' => [
            [
                'getMimeType',
                [],
                'application/x-empty',
            ],
            [
                'mimeType',
                [],
                $fileAttributes,
            ],
        ];
    }

    /**
     * @dataProvider provideListings
     */
    public function testListContents(string $path, bool $deep, array $expectedListing): void
    {
        $config = new Config();

        $rootAdapter = new InMemoryFilesystemAdapter();
        $rootAdapter->createDirectory('foo', $config);
        $rootAdapter->createDirectory('foo/bar', $config);
        $rootAdapter->createDirectory('files', $config);
        $rootAdapter->createDirectory('files/things', $config);
        $rootAdapter->createDirectory('files/special', $config);
        $rootAdapter->createDirectory('files/special/place', $config);
        $rootAdapter->write('file1', '', $config);
        $rootAdapter->write('foo/file2', '', $config);
        $rootAdapter->write('foo/bar/file3', '', $config);
        $rootAdapter->write('files/random.txt', '', $config);
        $rootAdapter->write('files/special/place/data.dat', '', $config);

        $filesSpecialAdapter = new InMemoryFilesystemAdapter();
        $filesSpecialAdapter->createDirectory('stuff', $config);
        $filesSpecialAdapter->write('foobar', '', $config);

        $filesMediaExtraAdapter = new InMemoryFilesystemAdapter();
        $filesMediaExtraAdapter->createDirectory('videos', $config);
        $filesMediaExtraAdapter->write('cat.avif', '', $config);
        $filesMediaExtraAdapter->write('videos/funny.mov', '', $config);

        $manager = new MountManager($rootAdapter);
        $manager->mount($filesSpecialAdapter, 'files/special');
        $manager->mount($filesMediaExtraAdapter, 'files/media/extra');

        // Get and normalize listing
        $listing = array_map(
            static fn (FilesystemItem $i): string => sprintf('%s (%s)', $i->getPath(), $i->isFile() ? 'file' : 'dir'),
            [...$manager->listContents($path, $deep)]
        );

        sort($listing);

        $this->assertSame($expectedListing, $listing);
    }

    public function provideListings(): \Generator
    {
        yield 'root, shallow' => [
            '', false,
            [
                'file1 (file)',
                'files (dir)',
                'foo (dir)',
            ],
        ];

        yield 'sub directory, shallow' => [
            'foo', false,
            [
                'foo/bar (dir)',
                'foo/file2 (file)',
            ],
        ];

        yield 'sub sub directory, shallow' => [
            'foo/bar', false,
            [
                'foo/bar/file3 (file)',
            ],
        ];

        yield 'sub directory, deep' => [
            'foo', true,
            [
                'foo/bar (dir)',
                'foo/bar/file3 (file)',
                'foo/file2 (file)',
            ],
        ];

        yield 'sub directory with mounts, shallow' => [
            'files', false,
            [
                'files/random.txt (file)',
                'files/special (dir)',
                'files/things (dir)',
            ],
        ];

        yield 'root, deep (including virtual directories from mounts)' => [
            '', true,
            [
                'file1 (file)',
                'files (dir)',
                // Note: 'files/media' must not be reported as a directory
                //       here, because it is virtual and implicit (i.e. only the
                //       explicitly mounted 'files/media/extra' is included).
                'files/media/extra (dir)',
                'files/media/extra/cat.avif (file)',
                'files/media/extra/videos (dir)',
                'files/media/extra/videos/funny.mov (file)',
                'files/random.txt (file)',
                'files/special (dir)',
                'files/special/foobar (file)',
                'files/special/stuff (dir)',
                'files/things (dir)',
                'foo (dir)',
                'foo/bar (dir)',
                'foo/bar/file3 (file)',
                'foo/file2 (file)',
            ],
        ];

        yield 'virtual parent directory, shallow' => [
            'files/media', false,
            [
                'files/media/extra (dir)',
            ],
        ];

        yield 'virtual parent directory, deep' => [
            'files/media', true,
            [
                'files/media/extra (dir)',
                'files/media/extra/cat.avif (file)',
                'files/media/extra/videos (dir)',
                'files/media/extra/videos/funny.mov (file)',
            ],
        ];
    }

    // todo: test copy + move

    private function mockFilesystemAdapterThatDoesNotReceiveACall(string $method): FilesystemAdapter
    {
        $adapter = $this->createMock(FilesystemAdapter::class);
        $adapter
            ->expects($this->never())
            ->method($method)
        ;

        return $adapter;
    }

    /**
     * @param mixed $return
     */
    private function mockFilesystemAdapterWithCall(string $method, array $expectedArguments, $return): FilesystemAdapter
    {
        $adapter = $this->createMock(FilesystemAdapter::class);

        $invocationMocker = $adapter
            ->expects($this->once())
            ->method($method)
            ->willReturnCallback(
                function (...$arguments) use ($expectedArguments): void {
                    foreach ($arguments as $index => $argument) {
                        if ($argument instanceof Config) {
                            $this->assertSame($expectedArguments[$index]->get('some_config'), $argument->get('some_config'));

                            return;
                        }

                        if ($argument instanceof FileAttributes) {
                            $this->assertSame($expectedArguments[$index]->jsonSerialize(), $argument->jsonSerialize());
                        }

                        $this->assertSame($expectedArguments[$index], $argument);
                    }
                }
            )
        ;

        if (null !== $return) {
            $invocationMocker->willReturn($return);
        }

        return $adapter;
    }

    private function closeStreamResources(array $arguments): void
    {
        foreach ($arguments as $resource) {
            if (\is_resource($resource)) {
                fclose($resource);
            }
        }
    }
}

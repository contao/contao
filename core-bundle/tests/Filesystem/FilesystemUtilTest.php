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
use Contao\CoreBundle\Filesystem\FilesystemUtil;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use Contao\StringUtil;
use Symfony\Component\Uid\Uuid;

class FilesystemUtilTest extends TestCase
{
    /**
     * @dataProvider provideResources
     */
    public function testListContentsFromSerialized(array|string $sources, array $expectedPaths): void
    {
        $storage = $this->createMock(VirtualFilesystemInterface::class);
        $storage
            ->method('get')
            ->willReturnCallback(
                static function (Uuid $uuid): FilesystemItem|null {
                    return match ($uuid->toRfc4122()) {
                        'd22b1ea8-dcab-4162-b690-30cb9206f694' => new FilesystemItem(true, 'file1'),
                        'b1817d6d-188a-4c99-9204-b1e33733d5a9' => new FilesystemItem(true, 'file2'),
                        '0af407bc-ced3-4688-9971-f30dca7005b6' => new FilesystemItem(true, 'directory/file3'),
                        'f0f4bde3-2e1f-48cb-9182-ba804868d93b' => new FilesystemItem(true, 'directory/file4'),
                        '1fc6c283-c0c8-420e-b1c7-712d388a6b3a' => new FilesystemItem(false, 'directory'),
                        default => null,
                    };
                },
            )
        ;

        $storage
            ->method('listContents')
            ->with('directory')
            ->willReturn(
                new FilesystemItemIterator([
                    new FilesystemItem(true, 'directory/file3'),
                    new FilesystemItem(true, 'directory/file4'),
                    new FilesystemItem(false, 'directory/subdirectory'),
                ]),
            )
        ;

        $paths = array_map(
            function (FilesystemItem $item): string {
                $this->assertTrue($item->isFile());

                return $item->getPath();
            },
            FilesystemUtil::listContentsFromSerialized($storage, $sources)->toArray(),
        );

        $this->assertSame($expectedPaths, $paths);
    }

    public function provideResources(): \Generator
    {
        $file1 = new Uuid('d22b1ea8-dcab-4162-b690-30cb9206f694');
        $file2 = new Uuid('b1817d6d-188a-4c99-9204-b1e33733d5a9');
        $file3 = new Uuid('0af407bc-ced3-4688-9971-f30dca7005b6');
        $directory = new Uuid('1fc6c283-c0c8-420e-b1c7-712d388a6b3a');

        yield 'single file as RFC 4122' => [
            $file1->toRfc4122(),
            ['file1'],
        ];

        yield 'single file as Contao binary UUID (StringUtil::uuidToBin)' => [
            StringUtil::uuidToBin($file1->toRfc4122()),
            ['file1'],
        ];

        yield 'single file as binary UUID (uuid_parse)' => [
            $file1->toBinary(),
            ['file1'],
        ];

        yield 'multiple files' => [
            [$file1->toBinary(), $file2->toBinary()],
            ['file1', 'file2'],
        ];

        yield 'files and directories' => [
            [$file1->toBinary(), $directory->toBinary()],
            ['file1', 'directory/file3', 'directory/file4'],
        ];

        yield 'duplicate files' => [
            [$file1->toBinary(), $directory->toBinary(), $file1->toBinary(), $file3->toBinary()],
            ['file1', 'directory/file3', 'directory/file4'],
        ];

        yield 'unknown UUID amongst valid' => [
            [$file1->toBinary(), (new Uuid('a1695de1-90a8-486c-9e2f-e0567cd9c6ab'))->toBinary()],
            ['file1'],
        ];

        yield 'unknown UUID' => [
            '',
            [],
        ];

        yield 'no files' => [
            [],
            [],
        ];

        yield 'mixed' => [
            [
                $file1->toRfc4122(),
                $directory->toRfc4122(),
                StringUtil::uuidToBin($file1->toRfc4122()),
                StringUtil::uuidToBin($file2->toRfc4122()),
                $file3->toBinary(),
            ],
            ['file1', 'directory/file3', 'directory/file4', 'file2'],
        ];
    }

    /**
     * @dataProvider provideInvalidArguments
     */
    public function testThrowIfArgumentIsNotAnOpenResource(mixed $argument, string $exception): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($exception);

        FilesystemUtil::assertIsResource($argument);
    }

    public function provideInvalidArguments(): \Generator
    {
        yield 'no resource' => [
            new \stdClass(),
            'Invalid stream provided, expected stream resource, received "object".',
        ];

        $resource = tmpfile();
        fclose($resource);

        yield 'closed resource' => [
            $resource,
            'Invalid stream provided, expected stream resource, received "resource (closed)".',
        ];

        $nonStreamResource = stream_context_create();

        yield 'non-stream resource' => [
            $nonStreamResource,
            'Invalid stream provided, expected stream resource, received resource of type "stream-context".',
        ];
    }

    public function testRewindStream(): void
    {
        $resource1 = tmpfile();
        $resource2 = tmpfile();
        fseek($resource2, 1);

        $this->assertSame(0, ftell($resource1));
        $this->assertSame(1, ftell($resource2));

        FilesystemUtil::rewindStream($resource1);
        FilesystemUtil::rewindStream($resource2);

        $this->assertSame(0, ftell($resource1));
        $this->assertSame(0, ftell($resource2));

        fclose($resource1);
        fclose($resource2);
    }
}

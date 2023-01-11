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

use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use Contao\CoreBundle\Tests\TestCase;

class VirtualFilesystemExceptionTest extends TestCase
{
    /**
     * @dataProvider provideVariants
     */
    public function testGetValues(VirtualFilesystemException $e, string $message): void
    {
        $this->assertSame($message, $e->getMessage());
        $this->assertSame('foo/bar', $e->getPath());
        $this->assertSame('previous', $e->getPrevious()->getMessage());
    }

    public function provideVariants(): \Generator
    {
        $previous = new \RuntimeException('previous');

        yield 'unable to check if file exists' => [
            VirtualFilesystemException::unableToCheckIfFileExists('foo/bar', $previous),
            'Unable to check if a file exists at "foo/bar".',
        ];

        yield 'unable to check if directory exists' => [
            VirtualFilesystemException::unableToCheckIfDirectoryExists('foo/bar', $previous),
            'Unable to check if a directory exists at "foo/bar".',
        ];

        yield 'unable to read' => [
            VirtualFilesystemException::unableToRead('foo/bar', $previous),
            'Unable to read from "foo/bar".',
        ];

        yield 'unable to write' => [
            VirtualFilesystemException::unableToWrite('foo/bar', $previous),
            'Unable to write to "foo/bar".',
        ];

        yield 'unable to delete file' => [
            VirtualFilesystemException::unableToDelete('foo/bar', $previous),
            'Unable to delete file at "foo/bar".',
        ];

        yield 'unable to delete directory' => [
            VirtualFilesystemException::unableToDeleteDirectory('foo/bar', $previous),
            'Unable to delete directory at "foo/bar".',
        ];

        yield 'unable to create directory' => [
            VirtualFilesystemException::unableToCreateDirectory('foo/bar', $previous),
            'Unable to create directory at "foo/bar".',
        ];

        yield 'unable to copy' => [
            VirtualFilesystemException::unableToCopy('foo/bar', 'path/to', $previous),
            'Unable to copy file from "foo/bar" to "path/to".',
        ];

        yield 'unable to move' => [
            VirtualFilesystemException::unableToMove('foo/bar', 'path/to', $previous),
            'Unable to move file from "foo/bar" to "path/to".',
        ];

        yield 'unable to list contents' => [
            VirtualFilesystemException::unableToListContents('foo/bar', $previous),
            'Unable to list contents from "foo/bar".',
        ];

        yield 'unable to retrieve metadata' => [
            VirtualFilesystemException::unableToRetrieveMetadata('foo/bar', $previous),
            'Unable to retrieve metadata from "foo/bar".',
        ];

        yield 'unable to retrieve metadata with reason' => [
            VirtualFilesystemException::unableToRetrieveMetadata('foo/bar', $previous, 'The adapter is to blame.'),
            'Unable to retrieve metadata from "foo/bar": The adapter is to blame.',
        ];
    }
}

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

use Contao\CoreBundle\Filesystem\MaximumStreamSizeExceededException;
use Contao\CoreBundle\Filesystem\SizeLimitingVirtualFilesystemWriter;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use PHPUnit\Framework\TestCase;

final class SizeLimitingVirtualFilesystemWriterTest extends TestCase
{
    public function testAllowsAStreamAtTheMaximumSize(): void
    {
        $stream = $this->createStream('content');
        $filesystem = $this->createMock(VirtualFilesystemInterface::class);
        $filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->with('example.txt', $stream)
            ->willReturnCallback(
                static function (string $location, $contents): void {
                    self::assertSame('content', stream_get_contents($contents));
                },
            )
        ;

        new SizeLimitingVirtualFilesystemWriter($filesystem)->writeStream('example.txt', $stream, 7);
    }

    public function testRejectsAStreamLargerThanTheMaximumSize(): void
    {
        $stream = $this->createStream('content');
        $filesystem = $this->createMock(VirtualFilesystemInterface::class);
        $filesystem
            ->expects($this->once())
            ->method('writeStream')
            ->willReturnCallback(
                static function (string $location, $contents): void {
                    stream_get_contents($contents);
                },
            )
        ;

        $writer = new SizeLimitingVirtualFilesystemWriter($filesystem);

        $this->expectException(MaximumStreamSizeExceededException::class);
        $this->expectExceptionMessage('The stream exceeds the maximum size of 6 bytes.');

        $writer->writeStream('example.txt', $stream, 6);
    }

    public function testTranslatesAWriteExceptionAfterTheMaximumSizeWasExceeded(): void
    {
        $stream = $this->createStream('content');
        $writeException = new \RuntimeException('Write failed.');
        $filesystem = $this->createStub(VirtualFilesystemInterface::class);
        $filesystem
            ->method('writeStream')
            ->willReturnCallback(
                static function (string $location, $contents) use ($writeException): void {
                    stream_get_contents($contents);

                    throw $writeException;
                },
            )
        ;

        $writer = new SizeLimitingVirtualFilesystemWriter($filesystem);

        try {
            $writer->writeStream('example.txt', $stream, 6);
            $this->fail('The oversized stream was not rejected.');
        } catch (MaximumStreamSizeExceededException $exception) {
            $this->assertSame($writeException, $exception->getPrevious());
        }
    }

    public function testRejectsAnInvalidMaximumSize(): void
    {
        $writer = new SizeLimitingVirtualFilesystemWriter($this->createStub(VirtualFilesystemInterface::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The maximum stream size must be non-negative.');

        $writer->writeStream('example.txt', $this->createStream(''), -1);
    }

    public function testPreservesUnrelatedWriteExceptions(): void
    {
        $stream = $this->createStream('content');
        $exception = new \RuntimeException('Write failed.');
        $filesystem = $this->createStub(VirtualFilesystemInterface::class);
        $filesystem
            ->method('writeStream')
            ->willThrowException($exception)
        ;

        $writer = new SizeLimitingVirtualFilesystemWriter($filesystem);

        try {
            $writer->writeStream('example.txt', $stream, 7);
            $this->fail('The write exception was not thrown.');
        } catch (\RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    /**
     * @return resource
     */
    private function createStream(string $contents)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }
}

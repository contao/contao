<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\State;

use ApiPlatform\Metadata\Get;
use Contao\ApiBundle\ApiPlatform\State\VirtualFilesystemContentStateProvider;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class VirtualFilesystemContentStateProviderTest extends TestCase
{
    public function testStreamsAFileAsAnAttachment(): void
    {
        $item = new FilesystemItem(true, 'documents/résumé.pdf', 123, 7, 'application/pdf');
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, 'content');
        rewind($stream);

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('get')
            ->with('documents/résumé.pdf')
            ->willReturn($item)
        ;

        $storage
            ->expects($this->once())
            ->method('readStream')
            ->with('documents/résumé.pdf')
            ->willReturn($stream)
        ;

        $request = Request::create('/', server: ['HTTP_RANGE' => 'bytes=0-2']);
        $response = $this->createProvider($storage)->provide(new Get(), ['path' => 'documents/résumé.pdf'], ['request' => $request]);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertSame('7', $response->headers->get('Content-Length'));
        $this->assertSame('none', $response->headers->get('Accept-Ranges'));
        $this->assertSame('no-cache, private', $response->headers->get('Cache-Control'));
        $this->assertSame('Thu, 01 Jan 1970 00:02:03 GMT', $response->headers->get('Last-Modified'));
        $this->assertSame("attachment; filename=r_sum_.pdf; filename*=utf-8''r%C3%A9sum%C3%A9.pdf", $response->headers->get('Content-Disposition'));
        $this->assertSame('content', $this->getResponseContent($response));
        $this->assertFalse(\is_resource($stream));
    }

    public function testFallsBackToTheGenericMimeType(): void
    {
        $stream = fopen('php://temp', 'w+');

        $storage = $this->createStub(VirtualFilesystem::class);
        $storage
            ->method('get')
            ->willReturn(new FilesystemItem(true, 'documents/file.unknown', null, 0))
        ;

        $storage
            ->method('readStream')
            ->willReturn($stream)
        ;

        $response = $this->createProvider($storage)->provide(new Get(), ['path' => 'documents/file.unknown']);

        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertNull($response->headers->get('Last-Modified'));
        $this->getResponseContent($response);
    }

    public function testReturnsNotModifiedWithoutOpeningAStream(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('get')
            ->with('documents/example.txt')
            ->willReturn(new FilesystemItem(true, 'documents/example.txt', 123, 7, 'text/plain'))
        ;

        $storage
            ->expects($this->never())
            ->method('readStream')
        ;

        $request = Request::create('/', server: ['HTTP_IF_MODIFIED_SINCE' => 'Thu, 01 Jan 1970 00:02:03 GMT']);
        $response = $this->createProvider($storage)->provide(new Get(), ['path' => 'documents/example.txt'], ['request' => $request]);

        $this->assertSame(304, $response->getStatusCode());
        $this->assertNull($response->headers->get('Content-Length'));
    }

    public function testRejectsAFileNameThatIsNotValidUtf8(): void
    {
        $storage = $this->createStub(VirtualFilesystem::class);
        $storage
            ->method('get')
            ->willReturn(new FilesystemItem(true, "documents/invalid-\xFF.txt", 123, 7, 'text/plain'))
        ;

        $this->expectException(VirtualFilesystemException::class);
        $this->expectExceptionCode(VirtualFilesystemException::ENCOUNTERED_INVALID_PATH);

        $this->createProvider($storage)->provide(new Get(), ['path' => "documents/invalid-\xFF.txt"]);
    }

    public function testProvidesHeadersWithoutOpeningAStreamForHeadRequests(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('get')
            ->with('documents/example.txt')
            ->willReturn(new FilesystemItem(true, 'documents/example.txt', 123, 7, 'text/plain'))
        ;

        $storage
            ->expects($this->never())
            ->method('readStream')
        ;

        $response = $this->createProvider($storage)->provide(
            new Get(),
            ['path' => 'documents/example.txt'],
            ['request' => Request::create('/', 'HEAD')],
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame('7', $response->headers->get('Content-Length'));
        $this->assertSame('', $response->getContent());
    }

    #[DataProvider('provideMissingFiles')]
    public function testReturnsNotFoundForMissingFilesAndDirectories(FilesystemItem|null $item): void
    {
        $storage = $this->createStub(VirtualFilesystem::class);
        $storage
            ->method('get')
            ->willReturn($item)
        ;

        $this->expectException(NotFoundHttpException::class);
        $this->createProvider($storage)->provide(new Get(), ['path' => 'documents']);
    }

    public static function provideMissingFiles(): iterable
    {
        yield 'missing file' => [null];
        yield 'directory' => [new FilesystemItem(false, 'documents')];
    }

    private function createProvider(VirtualFilesystem $storage): VirtualFilesystemContentStateProvider
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return new VirtualFilesystemContentStateProvider($storage, $security);
    }

    private function getResponseContent(Response $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}

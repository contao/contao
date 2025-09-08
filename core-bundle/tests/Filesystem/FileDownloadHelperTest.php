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

use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\Config;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\UriSigner;

class FileDownloadHelperTest extends TestCase
{
    private string $phpIniIgnoreUserAbort = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->phpIniIgnoreUserAbort = \ini_get('ignore_user_abort');
    }

    protected function tearDown(): void
    {
        ini_set('ignore_user_abort', $this->phpIniIgnoreUserAbort);

        parent::tearDown();
    }

    #[DataProvider('provideInlineContext')]
    public function testGenerateAndHandleInlineUrl(array|null $context): void
    {
        $helper = $this->getFileDownloadHelper();
        $url = $helper->generateInlineUrl('https://example.com/', 'my_file.txt', $context);

        $onProcess = function (FilesystemItem $item, array $resolvedContext) use ($context): Response|null {
            $this->assertSame('my_file.txt', $item->getPath());
            $this->assertSame($context ?? [], $resolvedContext);

            return null;
        };

        $response = $helper->handle(Request::create($url), $this->getInMemoryStorage(), $onProcess);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertNull($response->headers->get('Content-Disposition'));

        $this->assertSame('foo', $this->getResponseContent($response));
    }

    public static function provideInlineContext(): iterable
    {
        yield 'without context' => [
            null,
        ];

        yield 'with context' => [
            ['foo' => 'bar', 'foobar' => 'baz'],
        ];
    }

    #[DataProvider('provideDownloadContext')]
    public function testGenerateAndHandleDownloadUrl(string|null $fileName, array|null $context): void
    {
        $helper = $this->getFileDownloadHelper();
        $url = $helper->generateDownloadUrl('https://example.com/', 'my_file.txt', $fileName, $context);

        $onProcess = function (FilesystemItem $item, array $resolvedContext) use ($context): Response|null {
            $this->assertSame('my_file.txt', $item->getPath());
            $this->assertSame($context ?? [], $resolvedContext);

            return null;
        };

        $response = $helper->handle(Request::create($url), $this->getInMemoryStorage(), $onProcess);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename='.($fileName ?? 'my_file.txt'), $response->headers->get('Content-Disposition'));
        $this->assertSame('foo', $this->getResponseContent($response));
    }

    public function testHandleLocalDownloadUrl(): void
    {
        $adapter = new LocalFilesystemAdapter(Path::canonicalize(__DIR__.'/../Fixtures/files/data'));

        $mountManager = new MountManager();
        $mountManager->mount($adapter);

        $storage = new VirtualFilesystem($mountManager, $this->createMock(DbafsManager::class));

        $helper = $this->getFileDownloadHelper();
        $url = $helper->generateDownloadUrl('https://example.com/', 'data.csv', 'data.csv');
        $response = $helper->handle(Request::create($url), $storage);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('attachment; filename=data.csv', $response->headers->get('Content-Disposition'));
        $this->assertSame("foo,bar\n", $this->getResponseContent($response));
    }

    public function testGenerateAndHandleDownloadUrlUnknownMimeType(): void
    {
        $helper = $this->getFileDownloadHelper();
        $url = $helper->generateDownloadUrl('https://example.com/', 'my_file.unknown');

        $response = $helper->handle(Request::create($url), $this->getInMemoryStorage());

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=my_file.unknown', $response->headers->get('Content-Disposition'));
        $this->assertSame('foo', $this->getResponseContent($response));
    }

    public function testPreservesQueryParameters(): void
    {
        $helper = $this->getFileDownloadHelper();
        $url = $helper->generateDownloadUrl('https://example.com/path?foo=bar', 'my_file.txt');

        $this->assertTrue(str_ends_with($url, '&d=attachment&foo=bar&p=my_file.txt'));
    }

    public static function provideDownloadContext(): iterable
    {
        yield 'without filename or context' => [
            null,
            null,
        ];

        yield 'with filename' => [
            'custom_name.txt',
            null,
        ];

        yield 'with context' => [
            'custom_name.txt',
            ['foo' => 'bar', 'foobar' => 'baz'],
        ];

        yield 'with filename and context' => [
            'custom_name.txt',
            ['foo' => 'bar', 'foobar' => 'baz'],
        ];
    }

    private function getInMemoryStorage(): VirtualFilesystem
    {
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->write('my_file.txt', 'foo', new Config());
        $adapter->write('my_file.unknown', 'foo', new Config());

        $mountManager = new MountManager();
        $mountManager->mount($adapter);

        return new VirtualFilesystem($mountManager, $this->createMock(DbafsManager::class));
    }

    private function getResponseContent(Response $response): string
    {
        ob_start();

        $response->sendContent();

        return ob_get_clean();
    }

    private function getFileDownloadHelper(): FileDownloadHelper
    {
        return new FileDownloadHelper(new UriSigner('secret'));
    }
}

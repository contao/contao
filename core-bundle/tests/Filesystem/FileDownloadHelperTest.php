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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\UriSigner;

class FileDownloadHelperTest extends TestCase
{
    /**
     * @dataProvider provideInlineContext
     */
    public function testGenerateAndHandleInlineUrl(array|null $context, string $expectedUrl): void
    {
        $helper = $this->getFileDownloadHelper();
        $url = $helper->generateInlineUrl('https://example.com/', 'my_file.txt', $context);

        $this->assertSame($expectedUrl, $url);

        $onProcess = function (FilesystemItem $item, array $resolvedContext) use ($context): Response|null {
            $this->assertSame('my_file.txt', $item->getPath());
            $this->assertSame($context ?? [], $resolvedContext);

            return null;
        };

        $response = $helper->handle(Request::create($url), $this->getStorage(), $onProcess);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertNull($response->headers->get('Content-Disposition'));

        $this->assertSame('foo', $this->getResponseContent($response));
    }

    public function provideInlineContext(): \Generator
    {
        yield 'without context' => [
            null,
            'https://example.com/?_hash=EJ5W%2FRitv01mjcHnPITlKLKolvtEm2O%2BEa3Dq2jekXk%3D&p=my_file.txt',
        ];

        yield 'with context' => [
            ['foo' => 'bar', 'foobar' => 'baz'],
            'https://example.com/?_hash=eHSjRLDzC%2BNi9w%2BnpBMHNNy1Hfg3XNNz0SvzMNUEO6k%3D&ctx=a%3A2%3A%7Bs%3A3%3A%22foo%22%3Bs%3A3%3A%22bar%22%3Bs%3A6%3A%22foobar%22%3Bs%3A3%3A%22baz%22%3B%7D&p=my_file.txt',
        ];
    }

    /**
     * @dataProvider provideDownloadContext
     */
    public function testGenerateAndHandleDownloadUrl(string|null $fileName, array|null $context, string $expectedUrl): void
    {
        $helper = $this->getFileDownloadHelper();
        $url = $helper->generateDownloadUrl('https://example.com/', 'my_file.txt', $fileName, $context);

        $this->assertSame($expectedUrl, $url);

        $onProcess = function (FilesystemItem $item, array $resolvedContext) use ($context): Response|null {
            $this->assertSame('my_file.txt', $item->getPath());
            $this->assertSame($context ?? [], $resolvedContext);

            return null;
        };

        $response = $helper->handle(Request::create($url), $this->getStorage(), $onProcess);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename='.($fileName ?? 'my_file.txt'), $response->headers->get('Content-Disposition'));
        $this->assertSame('foo', $this->getResponseContent($response));
    }

    public function provideDownloadContext(): \Generator
    {
        yield 'without filename or context' => [
            null,
            null,
            'https://example.com/?_hash=3JBfgZdT%2FVdQWC3Xez3i6FA4egPMsOEZBPwLQIu9rbI%3D&d=attachment&p=my_file.txt',
        ];

        yield 'with filename' => [
            'custom_name.txt',
            null,
            'https://example.com/?_hash=layVAUuHQtmig0aouIJcfJAxxhzdkZyGKjMzy3RofJ4%3D&d=attachment&f=custom_name.txt&p=my_file.txt',
        ];

        yield 'with context' => [
            'custom_name.txt',
            ['foo' => 'bar', 'foobar' => 'baz'],
            'https://example.com/?_hash=m12mfxGRovabsM4wWsM2CFcL7B%2FaYhHMFvkSjWz9YR4%3D&ctx=a%3A2%3A%7Bs%3A3%3A%22foo%22%3Bs%3A3%3A%22bar%22%3Bs%3A6%3A%22foobar%22%3Bs%3A3%3A%22baz%22%3B%7D&d=attachment&f=custom_name.txt&p=my_file.txt',
        ];

        yield 'with filename and context' => [
            'custom_name.txt',
            ['foo' => 'bar', 'foobar' => 'baz'],
            'https://example.com/?_hash=m12mfxGRovabsM4wWsM2CFcL7B%2FaYhHMFvkSjWz9YR4%3D&ctx=a%3A2%3A%7Bs%3A3%3A%22foo%22%3Bs%3A3%3A%22bar%22%3Bs%3A6%3A%22foobar%22%3Bs%3A3%3A%22baz%22%3B%7D&d=attachment&f=custom_name.txt&p=my_file.txt',
        ];
    }

    private function getStorage(): VirtualFilesystem
    {
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->write('my_file.txt', 'foo', new Config());

        $mountManager = new MountManager();
        $mountManager->mount($adapter);

        return new VirtualFilesystem($mountManager, $this->createMock(DbafsManager::class));
    }

    private function getResponseContent(Response $response): string
    {
        ob_start();

        $response->send();

        return ob_get_clean();
    }

    private function getFileDownloadHelper(): FileDownloadHelper
    {
        return new FileDownloadHelper(new UriSigner('secret'));
    }
}

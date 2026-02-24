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
use Contao\CoreBundle\Filesystem\PublicUri\TemporaryAccessOption;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\Config;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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

    public function testHandleRequestRejectsInvalidSignature(): void
    {
        $helper = new FileDownloadHelper(
            $this->mockUriSigner(false),
            $this->createStub(RouterInterface::class),
            $this->createStub(Security::class),
            $this->createStub(MountManager::class),
        );

        $response = $helper->handleRequest(Request::create('https://example.com/?token='), 'files/my_file.txt');

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('The provided file URL is not valid.', $response->getContent());
    }

    public function testHandleRequestRejectsInvalidToken(): void
    {
        $helper = new FileDownloadHelper(
            $this->mockUriSigner(true),
            $this->createStub(RouterInterface::class),
            $this->mockSecurity($this->createStub(TokenInterface::class)),
            $this->createStub(MountManager::class),
        );

        $request = Request::create('https://example.com/?token=incorrect');
        $response = $helper->handleRequest($request, 'files/my_file.txt');

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame('The provided token is not valid.', $response->getContent());
    }

    public function testHandleRequestReturns404IfFileDoesNotExist(): void
    {
        $mountManager = $this->createMock(MountManager::class);
        $mountManager
            ->expects($this->once())
            ->method('get')
            ->with('files/missing.txt')
            ->willReturn(null)
        ;

        $helper = new FileDownloadHelper(
            $this->mockUriSigner(true),
            $this->createStub(RouterInterface::class),
            $this->mockSecurity(),
            $mountManager,
        );

        $response = $helper->handleRequest(Request::create('https://example.com/'), 'files/missing.txt');

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('The requested resource does not exist.', $response->getContent());
    }

    public function testHandleRequestStreamsFileAndSetsHeaders(): void
    {
        $file = $this->createMock(FilesystemItem::class);
        $file
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('files/my_file.txt')
        ;

        $file
            ->expects($this->once())
            ->method('getMimeType')
            ->willReturn('text/plain')
        ;

        $stream = fopen('php://temp', 'w+');
        fwrite($stream, 'foo');
        rewind($stream);

        $mountManager = $this->createMock(MountManager::class);
        $mountManager
            ->expects($this->once())
            ->method('get')
            ->with('files/my_file.txt')
            ->willReturn($file)
        ;

        $mountManager
            ->expects($this->once())
            ->method('readStream')
            ->with('files/my_file.txt')
            ->willReturn($stream)
        ;

        $helper = new FileDownloadHelper(
            $this->mockUriSigner(true),
            $this->createStub(RouterInterface::class),
            $this->mockSecurity(),
            $mountManager,
        );

        $request = Request::create('https://example.com/?d=attachment&f=custom_name.txt');
        $response = $helper->handleRequest($request, 'files/my_file.txt');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame('attachment; filename=custom_name.txt', $response->headers->get('Content-Disposition'));
        $this->assertSame('foo', $this->getResponseContent($response));
    }

    public function testGenerateUrlRejectsInvalidDisposition(): void
    {
        $helper = new FileDownloadHelper(
            new UriSigner('secret'),
            $this->createStub(RouterInterface::class),
            $this->createStub(Security::class),
            $this->createStub(MountManager::class),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The disposition must be either "attachment" or "inline".');

        $helper->generateUrl('files/my_file.txt', new TemporaryAccessOption(60, 'hash'), null, 'invalid');
    }

    public function testGenerateUrlGeneratesSignedUrlWithExpectedParametersWhenAnonymous(): void
    {
        $signer = new UriSigner('secret');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'contao_file_stream',
                $this->callback(
                    // No token when anonymous and no filename
                    static fn (array $params): bool => 'files/my_file.txt' === ($params['path'] ?? null)
                    && HeaderUtils::DISPOSITION_INLINE === ($params['d'] ?? null)
                    && 'content-hash' === ($params['ctx'] ?? null)
                    && !isset($params['t']),
                ),
                RouterInterface::ABSOLUTE_URL,
            )
            ->willReturnCallback(static fn (string $_route, array $params): string => 'https://example.com/_file_stream/'.$params['path'].'?'.http_build_query($params),
            )
        ;
        $helper = new FileDownloadHelper(
            $signer,
            $router,
            $this->mockSecurity(),
            $this->createStub(MountManager::class),
        );

        $url = $helper->generateUrl(
            'files/my_file.txt',
            new TemporaryAccessOption(60, 'content-hash'),
        );

        $this->assertStringContainsString('path=files%2Fmy_file.txt', $url);
        $this->assertStringContainsString('d=inline', $url);
        $this->assertStringContainsString('ctx=content-hash', $url);

        $this->assertTrue($signer->checkRequest(Request::create($url)));
    }

    public function testGenerateUrlThrowsIfFileNameIsInvalid(): void
    {
        $helper = new FileDownloadHelper(
            new UriSigner('secret'),
            $this->createStub(RouterInterface::class),
            $this->createStub(Security::class),
            $this->createStub(MountManager::class),
        );

        $this->expectException(\InvalidArgumentException::class);

        $helper->generateUrl(
            'files/my_file.txt',
            new TemporaryAccessOption(60, 'hash'),
            'résumé.pdf',
            HeaderUtils::DISPOSITION_ATTACHMENT,
        );
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

        $mountManager = new MountManager($this->createStub(FileDownloadHelper::class));
        $mountManager->mount($adapter);

        $storage = new VirtualFilesystem($mountManager, $this->createStub(DbafsManager::class));

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

    private function mockUriSigner(bool $checkRequestResult): UriSigner
    {
        $signer = $this->createMock(UriSigner::class);
        $signer
            ->expects($this->once())
            ->method('checkRequest')
            ->willReturn($checkRequestResult)
        ;

        return $signer;
    }

    private function mockSecurity(TokenInterface|null $token = null): Security
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('getToken')
            ->willReturn($token)
        ;

        return $security;
    }

    private function getInMemoryStorage(): VirtualFilesystem
    {
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->write('my_file.txt', 'foo', new Config());
        $adapter->write('my_file.unknown', 'foo', new Config());

        $mountManager = new MountManager($this->createStub(FileDownloadHelper::class));
        $mountManager->mount($adapter);

        return new VirtualFilesystem($mountManager, $this->createStub(DbafsManager::class));
    }

    private function getResponseContent(Response $response): string
    {
        ob_start();

        $response->sendContent();

        return ob_get_clean();
    }

    private function getFileDownloadHelper(): FileDownloadHelper
    {
        return new FileDownloadHelper(
            new UriSigner('secret'),
            $this->createStub(RouterInterface::class),
            $this->createStub(Security::class),
            $this->createStub(MountManager::class),
        );
    }
}

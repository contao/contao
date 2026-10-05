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

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Contao\ApiBundle\ApiPlatform\State\VirtualFilesystemStateProcessor;
use Contao\ApiBundle\Dto\VirtualFilesystemItemFactory;
use Contao\ApiBundle\Dto\VirtualFilesystemMove;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\ApiBundle\Serializer\VirtualFilesystemMetadataNormalizationHandler;
use Contao\Config;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\File\UploadSizeProvider;
use Contao\CoreBundle\File\UploadValidator;
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\TestCase\ContaoTestCase;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Metadata\MetadataBag as ImagineMetadataBag;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

final class VirtualFilesystemStateProcessorTest extends ContaoTestCase
{
    public function testRejectsDangerousUploadDestinations(): void
    {
        foreach (['shell.php', '.public', '.hidden/file.txt', 'file:stream.txt', 'file.txt/../shell.php'] as $path) {
            $storage = $this->createMock(VirtualFilesystem::class);
            $storage
                ->expects($this->never())
                ->method('writeStream')
            ;

            try {
                $this->createProcessor($storage)->process(null, new Put(), ['path' => $path], ['request' => Request::create('/', 'PUT', content: 'content')]);
                $this->fail('Accepted unsafe destination '.$path);
            } catch (BadRequestHttpException) {
                // Each destination must be rejected before writing.
            }
        }
    }

    public function testUploadsTheRequestBody(): void
    {
        $item = new FilesystemItem(true, 'documents/example.txt', 123, 7, 'text/plain');

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                'documents/example.txt',
                $this->callback(static fn ($stream): bool => \is_resource($stream) && 'content' === stream_get_contents($stream)),
            )
        ;

        $storage
            ->expects($this->once())
            ->method('get')
            ->with('documents/example.txt')
            ->willReturn($item)
        ;

        $processor = $this->createProcessor($storage);
        $result = $processor->process(null, new Put(), ['pathOrUuid' => 'documents/example.txt'], ['request' => Request::create('/', 'PUT', content: 'content')]);

        $this->assertSame('documents/example.txt', $result->path);
    }

    public function testRejectsUploadsLargerThanTheConfiguredMaximum(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
            ->willReturnCallback(
                static function (string $location, $contents): void {
                    stream_get_contents($contents);
                },
            )
        ;

        $processor = $this->createProcessor($storage, 3);

        try {
            $processor->process(null, new Put(), ['path' => 'example.txt'], ['request' => Request::create('/', 'PUT', content: 'four')]);
            $this->fail('The oversized upload was not rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(413, $exception->getStatusCode());
            $this->assertSame('The upload exceeds the maximum size of 3 bytes.', $exception->getMessage());
        }
    }

    public function testUploadsTheRequestBodyByUuid(): void
    {
        $uuid = Uuid::fromString('171bb68d-0094-4f6c-88f5-9b83c0d01521');
        $item = new FilesystemItem(true, 'documents/example.txt', 123, 7, 'text/plain');

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('resolveUuid')
            ->with($uuid)
            ->willReturn('documents/example.txt')
        ;

        $storage
            ->expects($this->once())
            ->method('writeStream')
            ->with(
                'documents/example.txt',
                $this->callback(static fn ($stream): bool => \is_resource($stream) && 'content' === stream_get_contents($stream)),
            )
        ;

        $storage
            ->expects($this->once())
            ->method('get')
            ->with('documents/example.txt')
            ->willReturn($item)
        ;

        $processor = $this->createProcessor($storage);
        $result = $processor->process(null, new Put(), ['pathOrUuid' => $uuid->toRfc4122()], ['request' => Request::create('/', 'PUT', content: 'content')]);

        $this->assertSame('documents/example.txt', $result->path);
    }

    public function testMovesTheItem(): void
    {
        $item = new FilesystemItem(true, 'archive/example.txt', 123, 7, 'text/plain');

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('move')
            ->with('documents/example.txt', 'archive/example.txt')
        ;

        $storage
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($item)
        ;

        $processor = $this->createProcessor($storage);
        $result = $processor->process(new VirtualFilesystemMove('documents/example.txt', 'archive/example.txt'), new Post());

        $this->assertSame('archive/example.txt', $result->path);
    }

    public function testMovesTheItemByUuid(): void
    {
        $sourceUuid = Uuid::fromString('171bb68d-0094-4f6c-88f5-9b83c0d01521');
        $destinationUuid = Uuid::fromString('26f19570-3e8e-4f03-9cf8-abc2a9bb6e0d');
        $item = new FilesystemItem(true, 'archive/example.txt', 123, 7, 'text/plain');

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->exactly(2))
            ->method('resolveUuid')
            ->willReturnCallback(static fn (Uuid $uuid): string => match ($uuid->toRfc4122()) {
                $destinationUuid->toRfc4122() => 'archive/example.txt',
                $sourceUuid->toRfc4122() => 'documents/example.txt',
                default => throw new \LogicException(\sprintf('Unexpected UUID "%s".', $uuid->toRfc4122())),
            })
        ;

        $storage
            ->expects($this->once())
            ->method('move')
            ->with('documents/example.txt', 'archive/example.txt')
        ;

        $storage
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturn($item)
        ;

        $processor = $this->createProcessor($storage);
        $result = $processor->process(new VirtualFilesystemMove($sourceUuid->toRfc4122(), $destinationUuid->toRfc4122()), new Post());

        $this->assertSame('archive/example.txt', $result->path);
    }

    public function testReturnsNotFoundForAnUnresolvedUuid(): void
    {
        $uuid = Uuid::fromString('171bb68d-0094-4f6c-88f5-9b83c0d01521');

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('resolveUuid')
            ->with($uuid)
            ->willThrowException(new UnableToResolveUuidException($uuid))
        ;

        $processor = $this->createProcessor($storage);

        $this->expectException(NotFoundHttpException::class);

        $processor->process(null, new Put(), ['path' => $uuid->toRfc4122()], ['request' => Request::create('/', 'PUT', content: 'content')]);
    }

    public function testUpdatesLocalizedMetadataWithoutLosingOtherMetadata(): void
    {
        $extra = new ExtraMetadata(['custom' => 'kept']);

        $extra->setLocalized(new MetadataBag([
            'en' => new Metadata(['title' => 'Old title', 'alt' => 'Old alt']),
            'de' => new Metadata(['title' => 'Deutscher Titel']),
        ]));

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('setExtraMetadata')
            ->with('images/example.jpg', $extra)
        ;

        $storage
            ->expects($this->exactly(2))
            ->method('get')
            ->with('images/example.jpg')
            ->willReturn(new FilesystemItem(true, 'images/example.jpg', 123, 7, 'image/jpeg', $extra))
        ;

        $processor = $this->createProcessor($storage);

        $result = $processor->process(
            null,
            $this->createMetadataOperation(),
            context: ['request' => Request::create('/', 'POST', content: '{"path":"images/example.jpg","data":{"localized":{"en":{"title":"New title"}}}}')],
        );

        $this->assertSame('New title', $result->metadata['localized']['en']['title']);
        $this->assertSame('kept', $result->metadata['custom']);
    }

    public function testUpdatesMetadataByUuid(): void
    {
        $uuid = Uuid::fromString('171bb68d-0094-4f6c-88f5-9b83c0d01521');
        $extra = new ExtraMetadata();

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->exactly(3))
            ->method('resolveUuid')
            ->with($uuid)
            ->willReturn('images/example.jpg')
        ;

        $storage
            ->expects($this->once())
            ->method('setExtraMetadata')
            ->with($uuid, $extra)
        ;

        $storage
            ->expects($this->exactly(2))
            ->method('get')
            ->with($uuid)
            ->willReturn(new FilesystemItem(true, 'images/example.jpg', 123, 7, 'image/jpeg', $extra))
        ;

        $processor = $this->createProcessor($storage);

        $result = $processor->process(
            null,
            $this->createMetadataOperation(),
            context: ['request' => Request::create('/', 'POST', content: \sprintf('{"path":"%s","data":{"custom":"value"}}', $uuid->toRfc4122()))],
        );

        $this->assertSame('value', $result->metadata['custom']);
    }

    public function testUpdatesImportantPartAndTextTrackMetadata(): void
    {
        $extra = new ExtraMetadata();

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('setExtraMetadata')
            ->with('images/example.jpg', $extra)
        ;

        $storage
            ->expects($this->exactly(2))
            ->method('get')
            ->with('images/example.jpg')
            ->willReturn(new FilesystemItem(true, 'images/example.jpg', 123, 7, 'image/jpeg', $extra))
        ;

        $processor = $this->createProcessor($storage);

        $result = $processor->process(
            null,
            $this->createMetadataOperation(),
            context: ['request' => Request::create('/', 'POST', content: '{"path":"images/example.jpg","data":{"importantPart":{"x":0.1,"y":0.2,"width":0.3,"height":0.4},"textTrack":{"sourceLanguage":"de","type":"captions"}}}')],
        );

        $this->assertSame(
            [
                'importantPart' => ['x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.4],
                'textTrack' => ['sourceLanguage' => 'de', 'type' => 'captions'],
            ],
            $result->metadata,
        );
    }

    public function testRejectsInvalidLocalizedMetadata(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->never())
            ->method('setExtraMetadata')
        ;

        $processor = $this->createProcessor($storage);

        $this->expectException(BadRequestHttpException::class);

        $processor->process(
            null,
            $this->createMetadataOperation(),
            context: ['request' => Request::create('/', 'POST', content: '{"path":"images/example.jpg","data":{"uuid":"changed"}}')],
        );
    }

    public function testRejectsMetadataWithoutAPath(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->never())
            ->method('setExtraMetadata')
        ;

        $processor = $this->createProcessor($storage);

        $this->expectException(BadRequestHttpException::class);

        $processor->process(
            null,
            $this->createMetadataOperation(),
            context: ['request' => Request::create('/', 'POST', content: '{"data":{}}')],
        );
    }

    public function testRejectsMetadataUpdatesForMissingFiles(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('get')
            ->with('missing.jpg')
            ->willReturn(null)
        ;

        $storage
            ->expects($this->never())
            ->method('setExtraMetadata')
        ;

        $processor = $this->createProcessor($storage);

        $this->expectException(NotFoundHttpException::class);

        $processor->process(
            null,
            $this->createMetadataOperation(),
            context: ['request' => Request::create('/', 'POST', content: '{"path":"missing.jpg","data":{"localized":{"en":{"title":"New title"}}}}')],
        );
    }

    private function createMetadataOperation(): Post
    {
        return new Post(extraProperties: ['contao' => ['operation' => 'metadata']]);
    }

    private function createSecurityStub(): Security
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return $security;
    }

    #[DataProvider('svgUploads')]
    public function testSanitizesSvgBeforeWriting(bool $compressed): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>';
        $path = $compressed ? 'image.svgz' : 'image.SVG';
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
            ->willReturnCallback(
                function ($location, $contents) use ($compressed): void {
                    $content = stream_get_contents($contents);
                    $content = $compressed ? gzdecode($content) : $content;
                    $this->assertStringNotContainsString('<script', $content);
                    $this->assertStringContainsString('<rect', $content);
                },
            )
        ;
        $storage
            ->method('get')
            ->willReturn(new FilesystemItem(true, $path))
        ;
        $result = $this->createProcessor($storage)->process(null, new Put(), ['path' => $path], ['request' => Request::create('/', 'PUT', content: $compressed ? gzencode($svg) : $svg)]);
        $this->assertSame($path, $result->path);
    }

    public static function svgUploads(): iterable
    {
        yield [false];
        yield [true];
    }

    public function testInvalidSvgNeverReachesStorage(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->never())
            ->method('writeStream')
        ;
        $this->expectException(BadRequestHttpException::class);
        $this->createProcessor($storage)->process(null, new Put(), ['path' => 'image.svg'], ['request' => Request::create('/', 'PUT', content: '<svg')]);
    }

    public function testOversizedSvgIsRejectedBeforeSanitizationCanShrinkIt(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->never())
            ->method('writeStream')
        ;

        try {
            $this->createProcessor($storage, 3)->process(null, new Put(), ['path' => 'image.svg'], ['request' => Request::create('/', 'PUT', content: '<svg/>')]);
            $this->fail('Accepted oversized SVG.');
        } catch (HttpException $exception) {
            $this->assertSame(413, $exception->getStatusCode());
        }
    }

    public function testRejectsUnsafeUuidDestination(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->method('resolveUuid')
            ->willReturn('.public')
        ;

        $storage
            ->expects($this->never())
            ->method('writeStream')
        ;
        $this->expectException(BadRequestHttpException::class);
        $this->createProcessor($storage)->process(null, new Put(), ['path' => '171bb68d-0094-4f6c-88f5-9b83c0d01521'], ['request' => Request::create('/', 'PUT', content: '')]);
    }

    public function testRejectsUnauthorizedUploadBeforeReadingContent(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->never())
            ->method('writeStream')
        ;
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('getContent')
        ;
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(false)
        ;
        $this->expectException(AccessDeniedException::class);
        $this->createProcessor($storage, security: $security)->process(null, new Put(), ['path' => 'image.svg'], ['request' => $request]);
    }

    #[DataProvider('invalidImageUploads')]
    public function testRemovesInvalidImageAfterWritingWithoutDeletePermission(string $content): void
    {
        $path = self::getTempDir().'/replacement.png';
        file_put_contents($path, 'old content');
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
            ->willReturnCallback(
                static function ($location, $contents) use ($path): void {
                    file_put_contents($path, $contents);
                },
            )
        ;
        $storage
            ->expects($this->once())
            ->method('readStream')
            ->willReturnCallback(
                function () use ($path, $content) {
                    $this->assertSame($content, file_get_contents($path));

                    return fopen($path, 'r');
                },
            )
        ;
        $storage
            ->expects($this->once())
            ->method('delete')
            ->with('replacement.png')
            ->willReturnCallback(
                static function () use ($path): void {
                    unlink($path);
                },
            )
        ;
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturnCallback(static fn ($permission): bool => ContaoCorePermissions::USER_CAN_DELETE_FILE !== $permission)
        ;

        try {
            $this->createProcessor($storage, checkImages: true, security: $security)->process(null, new Put(), ['path' => 'replacement.png'], ['request' => Request::create('/', 'PUT', content: $content)]);
            $this->fail('Accepted invalid image.');
        } catch (BadRequestHttpException) {
            $this->assertFileDoesNotExist($path);
        }
    }

    public static function invalidImageUploads(): iterable
    {
        yield 'unreadable' => ['not an image'];
        yield 'too wide' => ['GIF89a'.pack('vv', 2, 1).str_repeat("\0", 20)];
        yield 'too tall' => ['GIF89a'.pack('vv', 1, 2).str_repeat("\0", 20)];
    }

    public function testAcceptsImageAtDimensionLimitAfterWriting(): void
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWZkAAAAASUVORK5CYII=', true);
        $stored = null;
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
            ->willReturnCallback(
                static function ($location, $contents) use (&$stored): void {
                    $stored = stream_get_contents($contents);
                },
            )
        ;
        $storage
            ->expects($this->once())
            ->method('readStream')
            ->willReturnCallback(
                function () use (&$stored, $content) {
                    $this->assertSame($content, $stored);
                    $stream = fopen('php://temp', 'w+');
                    fwrite($stream, $stored);
                    rewind($stream);

                    return $stream;
                },
            )
        ;
        $storage
            ->expects($this->never())
            ->method('delete')
        ;

        $storage
            ->method('get')
            ->willReturn(new FilesystemItem(true, 'image.png'))
        ;
        $result = $this->createProcessor($storage, checkImages: true)->process(null, new Put(), ['path' => 'image.png'], ['request' => Request::create('/', 'PUT', content: $content)]);
        $this->assertSame('image.png', $result->path);
    }

    #[DataProvider('imageStreams')]
    public function testValidatesLocalStreamsDirectlyAndCopiesOtherStreams(bool $local): void
    {
        $contents = 'image handled by Imagine';
        $path = self::getTempDir().'/local-image.png';
        $stream = fopen($local ? $path : 'php://temp', 'w+');
        fwrite($stream, $contents);
        rewind($stream);
        $validatedPath = null;
        $image = $this->createStub(ImageInterface::class);
        $image
            ->method('getSize')
            ->willReturn(new Box(1, 1))
        ;

        $image
            ->method('metadata')
            ->willReturn(new ImagineMetadataBag())
        ;
        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->expects($this->once())
            ->method('open')
            ->willReturnCallback(
                function (string $actualPath) use ($local, $path, $contents, $image, &$validatedPath): ImageInterface {
                    $validatedPath = $actualPath;
                    if ($local) {
                        $this->assertSame($path, $actualPath);
                    } else {
                        $this->assertNotSame($path, $actualPath);
                    }
                    $this->assertSame($contents, file_get_contents($actualPath));

                    return $image;
                },
            )
        ;
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
        ;

        $storage
            ->expects($this->once())
            ->method('readStream')
            ->willReturn($stream)
        ;

        $storage
            ->method('get')
            ->willReturn(new FilesystemItem(true, 'image.png'))
        ;
        $this->createProcessor($storage, checkImages: true, imagine: $imagine)->process(null, new Put(), ['path' => 'image.png'], ['request' => Request::create('/', 'PUT', content: $contents)]);
        $this->assertFalse(\is_resource($stream));
        $this->assertNotNull($validatedPath);
        $this->assertSame($local, file_exists($validatedPath));
    }

    public static function imageStreams(): iterable
    {
        yield 'local file' => [true];
        yield 'temporary stream' => [false];
    }

    public function testSurfacesImageCleanupFailure(): void
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, 'invalid image');
        rewind($stream);
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
        ;

        $storage
            ->method('readStream')
            ->willReturn($stream)
        ;

        $storage
            ->expects($this->once())
            ->method('delete')
            ->willThrowException(new \RuntimeException('Cleanup failed.'))
        ;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cleanup failed.');
        $this->createProcessor($storage, checkImages: true)->process(null, new Put(), ['path' => 'image.png'], ['request' => Request::create('/', 'PUT', content: 'invalid image')]);
    }

    public function testSkipsImageInspectionWhenDisabled(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('writeStream')
        ;

        $storage
            ->expects($this->never())
            ->method('readStream')
        ;

        $storage
            ->method('get')
            ->willReturn(new FilesystemItem(true, 'image.png'))
        ;
        $this->createProcessor($storage)->process(null, new Put(), ['path' => 'image.png'], ['request' => Request::create('/', 'PUT', content: 'content')]);
    }

    #[DataProvider('unsafeMoves')]
    public function testRejectsUnsafeMoves(string $source, string $destination): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->method('get')
            ->willReturn(new FilesystemItem(true, $source))
        ;

        $storage
            ->expects($this->never())
            ->method('move')
        ;
        $this->expectException(BadRequestHttpException::class);
        $this->createProcessor($storage)->process(new VirtualFilesystemMove($source, $destination), new Post());
    }

    public static function unsafeMoves(): iterable
    {
        yield ['file.txt', 'file.php'];
        yield ['file.txt', 'file.svg'];
        yield ['file.txt', '.public'];
        yield ['.public', 'file.txt'];
        yield ['file.txt', '.hidden/file.txt'];
    }

    public function testMovesDirectoriesWithoutApplyingAnExtensionAllowlist(): void
    {
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->method('get')
            ->willReturn(new FilesystemItem(false, 'folder.new'))
        ;

        $storage
            ->expects($this->once())
            ->method('move')
            ->with('folder.old', 'folder.new')
        ;
        $this->createProcessor($storage)->process(new VirtualFilesystemMove('folder.old', 'folder.new'), new Post());
    }

    private function createProcessor(VirtualFilesystem $storage, int $maximumUploadSize = 1234, bool $checkImages = false, Security|null $security = null, ImagineInterface|null $imagine = null): VirtualFilesystemStateProcessor
    {
        $normalizer = $this->createObjectNormalizer();

        $config = $this->createAdapterStub(['get']);
        $config
            ->method('get')
            ->willReturnMap([['uploadTypes', 'txt,svg,svgz,png'], ['imageWidth', 1], ['imageHeight', 1]])
        ;
        $validator = new UploadValidator($this->createContaoFrameworkStub([Config::class => $config]), $checkImages, $imagine ?? new Imagine());

        return new VirtualFilesystemStateProcessor($storage, $security ?? $this->createSecurityStub(), new RequestStack(), $normalizer, new VirtualFilesystemItemFactory($normalizer), $this->createUploadSizeProvider($maximumUploadSize), $validator);
    }

    private function createUploadSizeProvider(int $maximumUploadSize): UploadSizeProvider
    {
        return new UploadSizeProvider($maximumUploadSize, $maximumUploadSize);
    }

    private function createObjectNormalizer(): SchemaAwareObjectNormalizer
    {
        return new SchemaAwareObjectNormalizer(new Validator(), [new VirtualFilesystemMetadataNormalizationHandler()]);
    }
}

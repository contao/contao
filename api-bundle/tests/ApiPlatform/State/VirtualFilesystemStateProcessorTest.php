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
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\File\UploadSizeProvider;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class VirtualFilesystemStateProcessorTest extends TestCase
{
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
        $result = $processor->process(null, new Put(), ['path' => 'documents/example.txt'], ['request' => Request::create('/', 'PUT', content: 'content')]);

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
            ->expects($this->once())
            ->method('get')
            ->with('archive/example.txt')
            ->willReturn($item)
        ;

        $processor = $this->createProcessor($storage);
        $result = $processor->process(new VirtualFilesystemMove('documents/example.txt', 'archive/example.txt'), new Post());

        $this->assertSame('archive/example.txt', $result->path);
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

    private function createProcessor(VirtualFilesystem $storage, int $maximumUploadSize = 1234): VirtualFilesystemStateProcessor
    {
        $normalizer = $this->createObjectNormalizer();

        return new VirtualFilesystemStateProcessor($storage, $this->createSecurityStub(), new RequestStack(), $normalizer, new VirtualFilesystemItemFactory($normalizer), $this->createUploadSizeProvider($maximumUploadSize));
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

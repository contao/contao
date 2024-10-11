<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\DbafsMetadataSubscriber;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\Filesystem\Dbafs\RetrieveDbafsMetadataEvent;
use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\ImportantPart;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class DbafsMetadataSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $subscriber = $this->getDbafsMetadataSubscriber();

        $this->assertSame(
            [
                RetrieveDbafsMetadataEvent::class => ['enhanceMetadata'],
                StoreDbafsMetadataEvent::class => ['normalizeMetadata'],
            ],
            $subscriber::getSubscribedEvents(),
        );
    }

    public function testEnhancesMetadata(): void
    {
        $event = new RetrieveDbafsMetadataEvent('tl_files', $this->getDemoRowData());

        $this->assertEmpty($event->getExtraMetadata()->all());

        $this->getDbafsMetadataSubscriber()->enhanceMetadata($event);

        $extraMetadata = $event->getExtraMetadata();
        $importantPart = $extraMetadata->getImportantPart();

        $this->assertInstanceOf(ImportantPart::class, $importantPart);
        $this->assertSame(0.1, $importantPart->getX());
        $this->assertSame(0.2, $importantPart->getY());
        $this->assertSame(0.3, $importantPart->getWidth());
        $this->assertSame(0.4, $importantPart->getHeight());

        $localizedMetadata = $extraMetadata->getLocalized();

        $this->assertInstanceOf(MetadataBag::class, $localizedMetadata);
        $this->assertInstanceOf(Metadata::class, $localizedMetadata['de']);
        $this->assertSame('my title', $localizedMetadata['de']->getTitle());
        $this->assertSame('f372c7d8-5aab-11ec-bf63-0242ac130002', $localizedMetadata['de']->getUuid());
    }

    public function testOnlyEnhancesMetadataOnDefaultTable(): void
    {
        $rowData = [
            'uuid' => Uuid::fromRfc4122('f372c7d8-5aab-11ec-bf63-0242ac130002')->toBinary(),
            'path' => 'path',
        ];

        $event = new RetrieveDbafsMetadataEvent('tl_foo', $rowData);

        $this->getDbafsMetadataSubscriber()->enhanceMetadata($event);

        $this->assertEmpty($event->getExtraMetadata()->all());
    }

    public function testSetsMetadataBagDefaultLocales(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->language = 'fr';
        $pageModel->rootFallbackLanguage = 'de';

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $event = new RetrieveDbafsMetadataEvent('tl_files', $this->getDemoRowData());

        $this->getDbafsMetadataSubscriber($requestStack)->enhanceMetadata($event);

        $localizedMetadata = $event->getExtraMetadata()->get('localized');

        $this->assertInstanceOf(MetadataBag::class, $localizedMetadata);

        $this->assertSame(
            ['fr', 'de'],
            (new \ReflectionClass(MetadataBag::class))
                ->getProperty('defaultLocales')
                ->getValue($localizedMetadata),
        );
    }

    public function testNormalizesMetadata(): void
    {
        $rowData = [
            'uuid' => Uuid::fromRfc4122('f372c7d8-5aab-11ec-bf63-0242ac130002')->toBinary(),
            'path' => 'foo/bar',
        ];

        $event = new StoreDbafsMetadataEvent('tl_files', $rowData, $this->getDemoMetadata());

        $this->assertSame($rowData, $event->getRow());

        $this->getDbafsMetadataSubscriber()->normalizeMetadata($event);

        $this->assertSame($this->getDemoRowData(), $event->getRow());
    }

    public function testOnlyNormalizesMetadataOnDefaultTable(): void
    {
        $rowData = [
            'uuid' => Uuid::fromRfc4122('f372c7d8-5aab-11ec-bf63-0242ac130002')->toBinary(),
            'path' => 'foo/bar',
        ];

        $event = new StoreDbafsMetadataEvent('tl_foo', $rowData);

        $this->getDbafsMetadataSubscriber()->normalizeMetadata($event);

        $this->assertSame($rowData, $event->getRow());
    }

    public function testThrowsIfUuidInFileMetadataDoesNotMatch(): void
    {
        $rowData = [
            'uuid' => Uuid::fromRfc4122('f372c7d8-5aab-11ec-bf63-0242ac130002')->toBinary(),
            'path' => 'foo/bar',
        ];

        $metadata = new ExtraMetadata([
            'localized' => new MetadataBag([
                'de' => new Metadata([
                    Metadata::VALUE_TITLE => 'my title',
                    Metadata::VALUE_UUID => '64c738b4-5aad-11ec-bf63-0242ac130002',
                ]),
            ]),
        ]);

        $event = new StoreDbafsMetadataEvent('tl_files', $rowData, $metadata);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The UUID stored in the file metadata (64c738b4-5aad-11ec-bf63-0242ac130002) does not match the one of the record (f372c7d8-5aab-11ec-bf63-0242ac130002).');

        $this->getDbafsMetadataSubscriber()->normalizeMetadata($event);
    }

    private function getDbafsMetadataSubscriber(RequestStack|null $requestStack = null): DbafsMetadataSubscriber
    {
        return new DbafsMetadataSubscriber(
            $requestStack ?? $this->createMock(RequestStack::class),
        );
    }

    private function getDemoRowData(): array
    {
        return [
            'uuid' => Uuid::fromRfc4122('f372c7d8-5aab-11ec-bf63-0242ac130002')->toBinary(),
            'path' => 'foo/bar',
            'importantPartX' => 0.1,
            'importantPartY' => 0.2,
            'importantPartWidth' => 0.3,
            'importantPartHeight' => 0.4,
            'meta' => serialize([
                'de' => [Metadata::VALUE_TITLE => 'my title'],
            ]),
        ];
    }

    private function getDemoMetadata(): ExtraMetadata
    {
        return new ExtraMetadata([
            'importantPart' => new ImportantPart(0.1, 0.2, 0.3, 0.4),
            'localized' => new MetadataBag([
                'de' => new Metadata([
                    Metadata::VALUE_TITLE => 'my title',
                    Metadata::VALUE_UUID => 'f372c7d8-5aab-11ec-bf63-0242ac130002',
                ]),
            ]),
        ]);
    }
}

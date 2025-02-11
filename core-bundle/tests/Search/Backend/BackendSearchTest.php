<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend;

use CmsIg\Seal\Adapter\Memory\MemoryAdapter;
use CmsIg\Seal\Adapter\Memory\MemoryStorage;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Reindex\ReindexConfig as SealReindexConfig;
use CmsIg\Seal\Schema\Index;
use Contao\CoreBundle\Event\BackendSearch\EnhanceHitEvent;
use Contao\CoreBundle\Job\Job;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Job\Owner;
use Contao\CoreBundle\Job\Status;
use Contao\CoreBundle\Messenger\Message\BackendSearch\DeleteDocumentsMessage;
use Contao\CoreBundle\Messenger\Message\BackendSearch\ReindexMessage;
use Contao\CoreBundle\Messenger\WebWorker;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Backend\Query;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Contao\CoreBundle\Search\Backend\Seal\SealReindexProvider;
use Contao\CoreBundle\Search\Backend\Seal\SealUtil;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class BackendSearchTest extends TestCase
{
    public function testIAvailable(): void
    {
        $webWorker = $this->createMock(WebWorker::class);
        $webWorker
            ->method('hasCliWorkersRunning')
            ->willReturn(true)
        ;

        $backendSearch = new BackendSearch(
            [],
            $this->createMock(Security::class),
            $this->createMock(EngineInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(Jobs::class),
            $webWorker,
            $this->createMock(SealReindexProvider::class),
        );

        $this->assertTrue($backendSearch->isAvailable());
    }

    public function testReindexSync(): void
    {
        $reindexConfig = (new ReindexConfig())
            ->limitToDocumentIds(new GroupedDocumentIds(['foo' => ['bar']])) // Non-existent document, must be deleted!
            ->limitToDocumentsNewerThan(new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))
            ->withJobId('foobar')
        ;

        $reindexProvider = $this->createMock(SealReindexProvider::class);

        $engine = $this->createMock(EngineInterface::class);
        $engine
            ->expects($this->once())
            ->method('reindex')
            ->with(
                [$reindexProvider],
                $this->callback(
                    static fn (SealReindexConfig $sealReindexConfig): bool => $reindexConfig->equals(SealUtil::sealReindexConfigToInternalReindexConfig($sealReindexConfig)->withJobId('foobar')),
                ),
            )
        ;

        $engine
            ->expects($this->once())
            ->method('bulk')
            ->with('contao_backend_search', [], ['foo__bar'])
        ;

        $jobs = $this->createMock(Jobs::class);
        $jobs
            ->expects($this->once())
            ->method('getByUuid')
            ->with('foobar')
            ->willReturn(Job::new(Owner::asSystem()))
        ;

        $expected = [
            Status::PENDING,
            Status::FINISHED,
        ];
        $jobs
            ->expects($this->exactly(2))
            ->method('persist')
            ->with($this->callback(
                static function (Job $job) use (&$expected) {
                    $status = $job->getStatus();
                    $pos = array_search($status, $expected, true);
                    unset($expected[$pos]);

                    return false !== $pos;
                }))
        ;

        $backendSearch = new BackendSearch(
            [$this->createMock(ProviderInterface::class)],
            $this->createMock(Security::class),
            $engine,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(MessageBusInterface::class),
            $jobs,
            $this->createMock(WebWorker::class),
            $reindexProvider,
        );

        $backendSearch->reindex($reindexConfig, false);
    }

    public function testReindexAsync(): void
    {
        $reindexConfig = (new ReindexConfig())
            ->limitToDocumentIds(new GroupedDocumentIds(['foo' => ['bar']]))
            ->limitToDocumentsNewerThan(new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))
        ;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (ReindexMessage $message): bool => '2024-01-01T00:00:00+00:00' === $message->getReindexConfig()->getUpdateSince()->format(\DateTimeInterface::ATOM) && ['foo' => ['bar']] === $message->getReindexConfig()->getLimitedDocumentIds()->toArray()))
            ->willReturn(new Envelope($this->createMock(ReindexMessage::class)))
        ;

        $backendSearch = new BackendSearch(
            [],
            $this->createMock(Security::class),
            $this->createMock(EngineInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $messageBus,
            $this->createMock(Jobs::class),
            $this->createMock(WebWorker::class),
            $this->createMock(SealReindexProvider::class),
        );

        $backendSearch->reindex($reindexConfig);
    }

    public function testSearch(): void
    {
        $indexName = 'contao_backend_search';

        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('supportsType')
            ->with('type')
            ->willReturn(true)
        ;

        $provider
            ->expects($this->once())
            ->method('convertDocumentToHit')
            ->with($this->callback(static fn (Document $document): bool => '42' === $document->getId()))
            ->willReturnCallback(static fn (Document $document): Hit => new Hit($document, 'human readable hit title', 'https://whatever.com'))
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with(
                ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_DOCUMENT,
                $this->callback(static fn (Document $document): bool => '42' === $document->getId()),
            )
            ->willReturn(true)
        ;

        $engine = new Engine(new MemoryAdapter(), BackendSearch::getSearchEngineSchema($indexName));
        $engine->createIndex($indexName);

        $engine->saveDocument($indexName, [
            'id' => 'type_42',
            'type' => 'type',
            'searchableContent' => 'search me',
            'tags' => [],
            'document' => '{"id":"42","type":"type","searchableContent":"search me","tags":[],"metadata":[]}',
        ]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (EnhanceHitEvent $event): bool => '42' === $event->getHit()->getDocument()->getId()))
        ;

        $backendSearch = new BackendSearch(
            [$provider],
            $security,
            $engine,
            $eventDispatcher,
            $this->createMock(MessageBusInterface::class),
            $this->createMock(Jobs::class),
            $this->createMock(WebWorker::class),
            $this->createMock(SealReindexProvider::class),
        );
        $result = $backendSearch->search(new Query(20, 'search me'));

        $this->assertSame('human readable hit title', $result->getHits()[0]->getTitle());
        $this->assertSame('42', $result->getHits()[0]->getDocument()->getId());

        // Cleanup memory
        MemoryStorage::dropIndex(new Index($indexName, []));
    }

    public function testExistingDocumentMatchesButProviderDoesNotConvertToHitWillTriggerDeletingThatDocument(): void
    {
        $indexName = 'contao_backend_search';

        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('supportsType')
            ->with('type')
            ->willReturn(true)
        ;

        $provider
            ->expects($this->once())
            ->method('convertDocumentToHit')
            ->with($this->callback(static fn (Document $document): bool => '42' === $document->getId()))
            ->willReturn(null) // No hit anymore
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with(
                ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_DOCUMENT,
                $this->callback(static fn (Document $document): bool => '42' === $document->getId()),
            )
            ->willReturn(true)
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->never())
            ->method('dispatch')
        ;

        $engine = new Engine(new MemoryAdapter(), BackendSearch::getSearchEngineSchema($indexName));
        $engine->createIndex($indexName);

        $engine->saveDocument($indexName, [
            'id' => 'type_42',
            'type' => 'type',
            'searchableContent' => 'search me',
            'tags' => [],
            'document' => '{"id":"42","type":"type","searchableContent":"search me","tags":[],"metadata":[]}',
        ]);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DeleteDocumentsMessage $message) => ['type' => ['42']] === $message->getGroupedDocumentIds()->toArray()))
            ->willReturn(new Envelope($this->createMock(DeleteDocumentsMessage::class)))
        ;

        $backendSearch = new BackendSearch(
            [$provider],
            $security,
            $engine,
            $eventDispatcher,
            $messageBus,
            $this->createMock(Jobs::class),
            $this->createMock(WebWorker::class),
            $this->createMock(SealReindexProvider::class),
        );

        $result = $backendSearch->search(new Query(20, 'search me'));

        $this->assertCount(0, $result->getHits());

        // Cleanup memory
        MemoryStorage::dropIndex(new Index($indexName, []));
    }

    public function testDeleteDocumentsSync(): void
    {
        $documentTypesAndIds = new GroupedDocumentIds([
            'test' => ['42'],
            'foobar' => ['42'],
        ]);

        $engine = $this->createMock(EngineInterface::class);
        $engine
            ->expects($this->once())
            ->method('bulk')
            ->with('contao_backend_search', [], ['test__42', 'foobar__42'])
        ;

        $backendSearch = new BackendSearch(
            [],
            $this->createMock(Security::class),
            $engine,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(MessageBusInterface::class),
            $this->createMock(Jobs::class),
            $this->createMock(WebWorker::class),
            $this->createMock(SealReindexProvider::class),
        );

        $backendSearch->deleteDocuments($documentTypesAndIds, false);
    }

    public function testDeleteDocumentsAsync(): void
    {
        $documentTypesAndIds = new GroupedDocumentIds([
            'test' => ['42'],
            'foobar' => ['42'],
        ]);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DeleteDocumentsMessage $message) => $documentTypesAndIds->toArray() === $message->getGroupedDocumentIds()->toArray()))
            ->willReturn(new Envelope($this->createMock(DeleteDocumentsMessage::class)))
        ;

        $backendSearch = new BackendSearch(
            [],
            $this->createMock(Security::class),
            $this->createMock(EngineInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $messageBus,
            $this->createMock(Jobs::class),
            $this->createMock(WebWorker::class),
            $this->createMock(SealReindexProvider::class),
        );

        $backendSearch->deleteDocuments($documentTypesAndIds);
    }
}

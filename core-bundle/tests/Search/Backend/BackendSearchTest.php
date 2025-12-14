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
use Contao\CoreBundle\Tests\Fixtures\Search\DocumentProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class BackendSearchTest extends TestCase
{
    public function testIAvailable(): void
    {
        $backendSearch = new BackendSearch(
            [],
            $this->createStub(Security::class),
            $this->createStub(EngineInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(MessageBusInterface::class),
            $this->createStub(Jobs::class),
            $this->createWebworkerWithCliRunning(),
            $this->createStub(SealReindexProvider::class),
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

        $reindexProvider = $this->createStub(SealReindexProvider::class);

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
            ->willReturn(Job::new(BackendSearch::REINDEX_JOB_TYPE, Owner::asSystem()))
        ;

        $expected = [
            Status::pending,
            Status::completed,
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
            [$this->createStub(ProviderInterface::class)],
            $this->createStub(Security::class),
            $engine,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(MessageBusInterface::class),
            $jobs,
            $this->createWebworkerWithCliRunning(),
            $reindexProvider,
        );

        $backendSearch->reindex($reindexConfig, false);
    }

    public function testReindexAsync(): void
    {
        $reindexConfig = (new ReindexConfig())
            ->withRequireJob(true)
            ->limitToDocumentIds(new GroupedDocumentIds(['foo' => ['bar']]))
            ->limitToDocumentsNewerThan(new \DateTimeImmutable('2024-01-01T00:00:00+00:00'))
        ;

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (ReindexMessage $message): bool => '2024-01-01T00:00:00+00:00' === $message->getReindexConfig()->getUpdateSince()->format(\DateTimeInterface::ATOM) && ['foo' => ['bar']] === $message->getReindexConfig()->getLimitedDocumentIds()->toArray()))
            ->willReturn(new Envelope($this->createStub(ReindexMessage::class)))
        ;

        $jobs = $this->createMock(Jobs::class);
        $jobs
            ->expects($this->once())
            ->method('createJob')
            ->with(BackendSearch::REINDEX_JOB_TYPE)
            ->willReturn(Job::new(BackendSearch::REINDEX_JOB_TYPE, Owner::asSystem()))
        ;

        $backendSearch = new BackendSearch(
            [],
            $this->createStub(Security::class),
            $this->createStub(EngineInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $messageBus,
            $jobs,
            $this->createWebworkerWithCliRunning(),
            $this->createStub(SealReindexProvider::class),
        );

        $backendSearch->reindex($reindexConfig);
    }

    public function testSearch(): void
    {
        $indexName = 'contao_backend_search';

        $provider = $this->createMock(DocumentProvider::class);
        $provider
            ->expects($this->atLeastOnce())
            ->method('supportsType')
            ->with('type')
            ->willReturn(true)
        ;

        $provider
            ->expects($this->exactly(3))
            ->method('convertTypeToVisibleType')
            ->with('type')
            ->willReturn('visible-type')
        ;

        $provider
            ->expects($this->once())
            ->method('getFacetLabelForTag')
            ->with('tag-1')
            ->willReturn('tag-1-label')
        ;

        $provider
            ->expects($this->exactly(2))
            ->method('convertDocumentToHit')
            ->with($this->callback(static fn (Document $document): bool => '42' === $document->getId()))
            ->willReturnCallback(static fn (Document $document): Hit => new Hit($document, 'human readable hit title', 'https://whatever.com'))
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
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
            'tags' => ['tag-1'],
            'document' => '{"id":"42","type":"type","searchableContent":"search me","tags":[],"metadata":[]}',
        ]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->with($this->callback(static fn (EnhanceHitEvent $event): bool => '42' === $event->getHit()->getDocument()->getId()))
        ;

        $backendSearch = new BackendSearch(
            [$provider],
            $security,
            $engine,
            $eventDispatcher,
            $this->createStub(MessageBusInterface::class),
            $this->createStub(Jobs::class),
            $this->createWebworkerWithCliRunning(),
            $this->createStub(SealReindexProvider::class),
        );

        // Test search without "type" -> should return type facets
        $result = $backendSearch->search(new Query(20, 'search me'));
        $this->assertSame('human readable hit title', $result->getHits()[0]->getTitle());
        $this->assertSame('42', $result->getHits()[0]->getDocument()->getId());
        $this->assertSame('type', $result->getTypeFacets()[0]->key);
        $this->assertSame('visible-type', $result->getTypeFacets()[0]->label);
        $this->assertCount(0, $result->getTagFacets());

        // Test search with "type" -> should return tag facets
        $result = $backendSearch->search(new Query(20, 'search me', 'type'));
        $this->assertSame('human readable hit title', $result->getHits()[0]->getTitle());
        $this->assertSame('42', $result->getHits()[0]->getDocument()->getId());
        $this->assertSame('tag-1', $result->getTagFacets()[0]->key);
        $this->assertSame('tag-1-label', $result->getTagFacets()[0]->label);
        $this->assertCount(0, $result->getTypeFacets());

        // Cleanup memory
        MemoryStorage::dropIndex(new Index($indexName, []));
    }

    public function testExistingDocumentMatchesButProviderDoesNotConvertToHitWillTriggerDeletingThatDocument(): void
    {
        $indexName = 'contao_backend_search';

        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->atLeastOnce())
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
            ->willReturn(new Envelope($this->createStub(DeleteDocumentsMessage::class)))
        ;

        $backendSearch = new BackendSearch(
            [$provider],
            $security,
            $engine,
            $eventDispatcher,
            $messageBus,
            $this->createStub(Jobs::class),
            $this->createWebworkerWithCliRunning(),
            $this->createStub(SealReindexProvider::class),
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
            $this->createStub(Security::class),
            $engine,
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(MessageBusInterface::class),
            $this->createStub(Jobs::class),
            $this->createWebworkerWithCliRunning(),
            $this->createStub(SealReindexProvider::class),
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
            ->willReturn(new Envelope($this->createStub(DeleteDocumentsMessage::class)))
        ;

        $backendSearch = new BackendSearch(
            [],
            $this->createStub(Security::class),
            $this->createStub(EngineInterface::class),
            $this->createStub(EventDispatcherInterface::class),
            $messageBus,
            $this->createStub(Jobs::class),
            $this->createWebworkerWithCliRunning(),
            $this->createStub(SealReindexProvider::class),
        );

        $backendSearch->deleteDocuments($documentTypesAndIds);
    }

    private function createWebworkerWithCliRunning(): WebWorker
    {
        $webWorker = $this->createStub(WebWorker::class);
        $webWorker
            ->method('hasCliWorkersRunning')
            ->willReturn(true)
        ;

        return $webWorker;
    }
}

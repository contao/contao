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

use Contao\CoreBundle\Event\BackendSearch\EnhanceHitEvent;
use Contao\CoreBundle\Event\BackendSearch\IndexDocumentEvent;
use Contao\CoreBundle\Messenger\Message\BackendSearch\DeleteDocumentsMessage;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\IndexUpdateConfigInterface;
use Contao\CoreBundle\Search\Backend\Provider\ProviderInterface;
use Contao\CoreBundle\Search\Backend\Query;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use PHPUnit\Framework\TestCase;
use Schranz\Search\SEAL\Adapter\Memory\MemoryAdapter;
use Schranz\Search\SEAL\Adapter\Memory\MemoryStorage;
use Schranz\Search\SEAL\Engine;
use Schranz\Search\SEAL\EngineInterface;
use Schranz\Search\SEAL\Schema\Index;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class BackendSearchTest extends TestCase
{
    public function testTriggerUpdate(): void
    {
        $engine = $this->createMock(EngineInterface::class);
        $engine
            ->expects($this->once())
            ->method('saveDocument')
            ->with(
                'contao_backend_search',
                $this->callback(
                    function (array $document): bool {
                        $this->assertSame('type_id', $document['id']);
                        $this->assertSame('type', $document['type']);
                        $this->assertSame('search me', $document['searchableContent']);
                        $this->assertSame([], $document['tags']);
                        $this->assertSame('{"id":"id","type":"type","searchableContent":"search me","tags":[],"metadata":[]}', $document['document']);

                        return true;
                    },
                ),
            )
        ;

        $indexUpdateConfig = $this->createMock(IndexUpdateConfigInterface::class);

        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('updateIndex')
            ->with($indexUpdateConfig)
            ->willReturnCallback(
                static function () {
                    yield new Document('id', 'type', 'search me');
                },
            )
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (IndexDocumentEvent $event): bool => 'id' === $event->getDocument()->getId()))
        ;

        $backendSearch = new BackendSearch(
            [$provider],
            $this->createMock(Security::class),
            $engine,
            $eventDispatcher,
            $this->createMock(MessageBusInterface::class),
            'contao_backend_search',
        );

        $backendSearch->triggerUpdate($indexUpdateConfig);
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
                ContaoCorePermissions::USER_CAN_ACCESS_BACKEND_SEARCH_HIT,
                $this->callback(static fn (Hit $hit): bool => '42' === $hit->getDocument()->getId()),
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

        $backendSearch = new BackendSearch([$provider], $security, $engine, $eventDispatcher, $this->createMock(MessageBusInterface::class), $indexName);
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
            ->with($this->callback(static fn (DeleteDocumentsMessage $message) => ['type_42'] === $message->getDocumentIds()))
            ->willReturn(new Envelope($this->createMock(DeleteDocumentsMessage::class)))
        ;

        $backendSearch = new BackendSearch(
            [$provider],
            $this->createMock(Security::class),
            $engine,
            $eventDispatcher,
            $messageBus,
            $indexName,
        );

        $result = $backendSearch->search(new Query(20, 'search me'));

        $this->assertCount(0, $result->getHits());

        // Cleanup memory
        MemoryStorage::dropIndex(new Index($indexName, []));
    }

    public function testDeleteDocumentsSync(): void
    {
        $documents = ['test_42', new Document('42', 'foobar', 'foo')];

        $engine = $this->createMock(EngineInterface::class);
        $engine
            ->expects($this->exactly(2))
            ->method('deleteDocument')
            ->withConsecutive(
                ['contao_backend_search', 'test_42'],
                ['contao_backend_search', 'foobar_42'],
            )
        ;

        $backendSearch = new BackendSearch(
            [],
            $this->createMock(Security::class),
            $engine,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(MessageBusInterface::class),
            'contao_backend_search',
        );

        $backendSearch->deleteDocuments($documents, false);
    }

    public function testDeleteDocumentsAsync(): void
    {
        $documents = ['test_42', new Document('42', 'foobar', 'foo')];

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (DeleteDocumentsMessage $message) => ['test_42', 'foobar_42'] === $message->getDocumentIds()))
            ->willReturn(new Envelope($this->createMock(DeleteDocumentsMessage::class)))
        ;

        $backendSearch = new BackendSearch(
            [],
            $this->createMock(Security::class),
            $this->createMock(EngineInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $messageBus,
            'contao_backend_search',
        );

        $backendSearch->deleteDocuments($documents);
    }
}

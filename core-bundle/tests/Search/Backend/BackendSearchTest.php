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
                        $this->assertSame('type.id', $document['id']);
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

        $backendSearch = new BackendSearch(
            [$provider],
            $this->createMock(Security::class),
            $engine,
            'contao_backend_search',
        );

        $backendSearch->triggerUpdate($indexUpdateConfig);
    }

    public function testSearch(): void
    {
        $indexName = 'contao_backend_search';
        $hit = new Hit('title', 'https://whatever.com');

        $provider = $this->createMock(ProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('supportsType')
            ->with('foobarType')
            ->willReturn(true)
        ;

        $provider
            ->expects($this->once())
            ->method('convertDocumentToHit')
            ->with($this->callback(static fn (Document $document): bool => '42' === $document->getId()))
            ->willReturn($hit)
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
            'id' => 'foobarType.42',
            'type' => 'foobarType',
            'searchableContent' => 'search me',
            'tags' => [],
            'document' => '{"id":"42","type":"type","searchableContent":"search me","tags":[],"metadata":[]}',
        ]);

        $backendSearch = new BackendSearch([$provider], $security, $engine, $indexName);
        $result = $backendSearch->search(new Query(20, 'search me'));

        $this->assertSame($hit, $result->getHits()[0]);

        // Cleanup memory
        MemoryStorage::dropIndex(new Index($indexName, []));
    }
}

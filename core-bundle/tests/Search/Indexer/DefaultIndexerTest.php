<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Indexer;

use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\DefaultIndexer;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\Search;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Driver\Connection;
use Nyholm\Psr7\Uri;

class DefaultIndexerTest extends ContaoTestCase
{
    /**
     * @dataProvider indexProvider
     */
    public function testIndexesADocument(Document $document, ?array $expectedIndexParams, string $expectedMessage = null, bool $indexProtected = false): void
    {
        $searchAdapter = $this->mockAdapter(['indexPage']);

        if (null === $expectedIndexParams) {
            $searchAdapter
                ->expects($this->never())
                ->method('indexPage')
            ;
        } else {
            $searchAdapter
                ->expects($this->once())
                ->method('indexPage')
                ->with($expectedIndexParams)
            ;
        }

        $framework = $this->mockContaoFramework([Search::class => $searchAdapter]);

        if (null !== $expectedIndexParams) {
            $framework
                ->expects($this->once())
                ->method('initialize')
            ;
        }

        if (null !== $expectedMessage) {
            $this->expectException(IndexerException::class);
            $this->expectExceptionMessage($expectedMessage);
        }

        $indexer = new DefaultIndexer($framework, $this->createMock(Connection::class), $indexProtected);
        $indexer->index($document);
    }

    public function indexProvider(): \Generator
    {
        yield 'Test does not index on empty content' => [
            new Document(new Uri('https://example.com'), 200, [], ''),
            null,
            'Cannot index empty response.',
        ];

        yield 'Test does not index if noSearch is set to true' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":true,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            null,
            'Was explicitly marked "noSearch" in page settings.',
        ];

        yield 'Test does not index if json ld data is not of type "PageMetaData"' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"FoobarType","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            null,
            'Was explicitly marked "noSearch" in page settings.',
        ];

        yield 'Test does not index if protected is set to true' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":false,"protected":true,"groups":[],"fePreview":false}</script></body></html>'),
            null,
            'Indexing protected pages is disabled.',
        ];

        yield 'Test valid index when not protected' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com',
                'content' => '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>',
                'protected' => '',
                'groups' => [],
                'pid' => 2,
                'title' => 'undefined',
                'language' => 'en',
            ],
        ];

        yield 'Test valid index when protected and index protected is enabled' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"title":"Foo title","language":"de","noSearch":false,"protected":true,"groups":[42],"fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com',
                'content' => '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"title":"Foo title","language":"de","noSearch":false,"protected":true,"groups":[42],"fePreview":false}</script></body></html>',
                'protected' => '1',
                'groups' => [42],
                'pid' => 2,
                'title' => 'Foo title',
                'language' => 'de',
            ],
            null,
            true,
        ];
    }

    public function testDeletesADocument(): void
    {
        $searchAdapter = $this->mockAdapter(['removeEntry']);
        $searchAdapter
            ->expects($this->once())
            ->method('removeEntry')
            ->with('https://example.com')
        ;

        $framework = $this->mockContaoFramework([Search::class => $searchAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $indexer = new DefaultIndexer($framework, $this->createMock(Connection::class));
        $indexer->delete(new Document(new Uri('https://example.com'), 200, [], ''));
    }

    public function testClearsTheSearchIndex(): void
    {
        $framework = $this->mockContaoFramework();

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['TRUNCATE TABLE tl_search'],
                ['TRUNCATE TABLE tl_search_index']
            )
        ;

        $indexer = new DefaultIndexer($framework, $connection);
        $indexer->clear();
    }
}

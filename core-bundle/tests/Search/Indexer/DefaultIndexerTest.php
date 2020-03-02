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
use Contao\PageModel;
use Contao\Search;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Driver\Connection;
use Nyholm\Psr7\Uri;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

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

        $urlMatcher = $this->createMock(UrlMatcherInterface::class);
        $urlMatcher
            ->expects('/valid' === $document->getUri()->getPath() ? $this->once() : $this->never())
            ->method('match')
            ->with('/valid')
            ->willReturn(['pageModel' => $this->mockClassWithProperties(PageModel::class, ['id' => 2])])
        ;

        $indexer = new DefaultIndexer($framework, $this->createMock(Connection::class), $urlMatcher, $indexProtected);
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
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"RegularPage","noSearch":true,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            null,
            'Was explicitly marked "noSearch" in page settings.',
        ];

        yield 'Test does not index if there is no JSON-LD data' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body></body></html>'),
            null,
            'No JSON-LD found.',
        ];

        yield 'Test does not index if JSON-LD data is not of type "RegularPage"' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"FoobarType","noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            null,
            'No JSON-LD found.',
        ];

        yield 'Test does not index if protected is set to true' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"RegularPage","noSearch":false,"protected":true,"groups":[],"fePreview":false}</script></body></html>'),
            null,
            'Indexing protected pages is disabled.',
        ];

        yield 'Test valid index when not protected' => [
            new Document(new Uri('https://example.com/valid'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"RegularPage","noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com/valid',
                'content' => '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"RegularPage","noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>',
                'protected' => '',
                'groups' => [],
                'pid' => 2,
                'title' => 'undefined',
                'language' => 'en',
            ],
        ];

        yield 'Test valid index when protected and index protected is enabled' => [
            new Document(new Uri('https://example.com/valid'), 200, [], '<html lang="de"><head><title>Foo title</title></head><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"RegularPage","noSearch":false,"protected":true,"groups":[42],"fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com/valid',
                'content' => '<html lang="de"><head><title>Foo title</title></head><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"RegularPage","noSearch":false,"protected":true,"groups":[42],"fePreview":false}</script></body></html>',
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

        $indexer = new DefaultIndexer($framework, $this->createMock(Connection::class), $this->createMock(UrlMatcherInterface::class));
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

        $indexer = new DefaultIndexer($framework, $connection, $this->createMock(UrlMatcherInterface::class));
        $indexer->clear();
    }
}

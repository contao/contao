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
use Contao\CoreBundle\Tests\TestCase;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Nyholm\Psr7\Uri;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class DefaultIndexerTest extends TestCase
{
    use ExpectDeprecationTrait;

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

        yield 'Test does not index if rel="canonical" does not match current page' => [
            new Document(new Uri('https://example.com/page'), 200, [], '<html><head><link rel="canonical" href="https://example.com/other-page" /></head><body></body></html>'),
            null,
            'Ignored because canonical URI "https://example.com/other-page" does not match document URI.',
        ];

        yield 'Test does not index if page ID could not be determined' => [
            new Document(new Uri('https://example.com/no-page-id'), 200, [], '<html><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:noSearch":false,"contao:protected":false,"contao:groups":[],"contao:fePreview":false}</script></body></html>'),
            null,
            'No page ID could be determined.',
        ];

        yield 'Test does not index if noSearch is set to true' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:pageId":2,"contao:noSearch":true,"contao:protected":false,"contao:groups":[],"contao:fePreview":false}</script></body></html>'),
            null,
            'Was explicitly marked "noSearch" in page settings.',
        ];

        yield 'Test does not index if there is no JSON-LD data' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body></body></html>'),
            null,
            'No JSON-LD found.',
        ];

        yield 'Test does not index if JSON-LD data is not of type "contao:Page"' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:FoobarType","contao:pageId":2,"contao:noSearch":false,"contao:protected":false,"contao:groups":[],"contao:fePreview":false}</script></body></html>'),
            null,
            'No JSON-LD found.',
        ];

        yield 'Test does not index if protected is set to true' => [
            new Document(new Uri('https://example.com'), 200, [], '<html><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:pageId":2,"contao:noSearch":false,"contao:protected":true,"contao:groups":[],"contao:fePreview":false}</script></body></html>'),
            null,
            'Indexing protected pages is disabled.',
        ];

        yield 'Test valid index when not protected' => [
            new Document(new Uri('https://example.com/valid'), 200, [], '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"Page","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com/valid',
                'content' => '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"Page","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>',
                'protected' => '',
                'groups' => [],
                'pid' => 2,
                'title' => 'undefined',
                'language' => 'en',
                'meta' => [
                    [
                        '@context' => 'https://schema.contao.org/',
                        '@type' => 'https://schema.contao.org/Page',
                        'https://schema.contao.org/pageId' => 2,
                        'https://schema.contao.org/noSearch' => false,
                        'https://schema.contao.org/protected' => false,
                        'https://schema.contao.org/groups' => [],
                        'https://schema.contao.org/fePreview' => false,
                    ],
                ],
            ],
        ];

        yield 'Test valid index when protected and index protected is enabled' => [
            new Document(new Uri('https://example.com/valid'), 200, [], '<html lang="de"><head><title>Foo title</title></head><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:pageId":2,"contao:noSearch":false,"contao:protected":true,"contao:groups":[42],"contao:fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com/valid',
                'content' => '<html lang="de"><head><title>Foo title</title></head><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:pageId":2,"contao:noSearch":false,"contao:protected":true,"contao:groups":[42],"contao:fePreview":false}</script></body></html>',
                'protected' => '1',
                'groups' => [42],
                'pid' => 2,
                'title' => 'Foo title',
                'language' => 'de',
                'meta' => [
                    [
                        '@context' => ['contao' => 'https://schema.contao.org/'],
                        '@type' => 'https://schema.contao.org/Page',
                        'https://schema.contao.org/pageId' => 2,
                        'https://schema.contao.org/noSearch' => false,
                        'https://schema.contao.org/protected' => true,
                        'https://schema.contao.org/groups' => [42],
                        'https://schema.contao.org/fePreview' => false,
                    ],
                ],
            ],
            null,
            true,
        ];

        yield 'Test valid index with page title' => [
            new Document(new Uri('https://example.com/valid'), 200, [], '<html lang="de"><head><title>HTML page title</title></head><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:title":"JSON-LD page title","contao:pageId":2,"contao:noSearch":false,"contao:protected":true,"contao:groups":[42],"contao:fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com/valid',
                'content' => '<html lang="de"><head><title>HTML page title</title></head><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:title":"JSON-LD page title","contao:pageId":2,"contao:noSearch":false,"contao:protected":true,"contao:groups":[42],"contao:fePreview":false}</script></body></html>',
                'protected' => '1',
                'groups' => [42],
                'pid' => 2,
                'title' => 'JSON-LD page title',
                'language' => 'de',
                'meta' => [
                    [
                        '@context' => ['contao' => 'https://schema.contao.org/'],
                        '@type' => 'https://schema.contao.org/Page',
                        'https://schema.contao.org/title' => 'JSON-LD page title',
                        'https://schema.contao.org/pageId' => 2,
                        'https://schema.contao.org/noSearch' => false,
                        'https://schema.contao.org/protected' => true,
                        'https://schema.contao.org/groups' => [42],
                        'https://schema.contao.org/fePreview' => false,
                    ],
                ],
            ],
            null,
            true,
        ];

        yield 'Test valid index with self-referencing rel="canonical"' => [
            new Document(new Uri('https://example.com/valid'), 200, [], '<html lang="de"><head><title>HTML page title</title><link rel="canonical" href="https://example.com/valid" /></head><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:title":"JSON-LD page title","contao:pageId":2,"contao:noSearch":false,"contao:protected":true,"contao:groups":[42],"contao:fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com/valid',
                'content' => '<html lang="de"><head><title>HTML page title</title><link rel="canonical" href="https://example.com/valid" /></head><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:title":"JSON-LD page title","contao:pageId":2,"contao:noSearch":false,"contao:protected":true,"contao:groups":[42],"contao:fePreview":false}</script></body></html>',
                'protected' => '1',
                'groups' => [42],
                'pid' => 2,
                'title' => 'JSON-LD page title',
                'language' => 'de',
                'meta' => [
                    [
                        '@context' => ['contao' => 'https://schema.contao.org/'],
                        '@type' => 'https://schema.contao.org/Page',
                        'https://schema.contao.org/title' => 'JSON-LD page title',
                        'https://schema.contao.org/pageId' => 2,
                        'https://schema.contao.org/noSearch' => false,
                        'https://schema.contao.org/protected' => true,
                        'https://schema.contao.org/groups' => [42],
                        'https://schema.contao.org/fePreview' => false,
                    ],
                ],
            ],
            null,
            true,
        ];
    }

    /**
     * @group legacy
     * @dataProvider indexProviderDeprecated
     */
    public function testIndexesADocumentWithDeprecatedJsonLd(Document $document, ?array $expectedIndexParams, string $expectedMessage = null, bool $indexProtected = false): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.9: Using the JSON-LD type "RegularPage" has been deprecated and will no longer work in Contao 5.0. Use "Page" instead.');

        $this->testIndexesADocument($document, $expectedIndexParams, $expectedMessage, $indexProtected);
    }

    public function indexProviderDeprecated(): \Generator
    {
        yield 'Test valid index when using deprecated JSON-LD @type RegularPage' => [
            new Document(new Uri('https://example.com/valid'), 200, [], '<html><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:RegularPage","contao:pageId":2,"contao:noSearch":false,"contao:protected":false,"contao:groups":[],"contao:fePreview":false}</script></body></html>'),
            [
                'url' => 'https://example.com/valid',
                'content' => '<html><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:RegularPage","contao:pageId":2,"contao:noSearch":false,"contao:protected":false,"contao:groups":[],"contao:fePreview":false}</script></body></html>',
                'protected' => '',
                'groups' => [],
                'pid' => 2,
                'title' => 'undefined',
                'language' => 'en',
                'meta' => [
                    [
                        '@context' => ['contao' => 'https://schema.contao.org/'],
                        '@type' => 'https://schema.contao.org/RegularPage',
                        'https://schema.contao.org/pageId' => 2,
                        'https://schema.contao.org/noSearch' => false,
                        'https://schema.contao.org/protected' => false,
                        'https://schema.contao.org/groups' => [],
                        'https://schema.contao.org/fePreview' => false,
                    ],
                ],
            ],
            null,
            false,
        ];
    }

    public function testDeletesADocument(): void
    {
        $connection = $this->createMock(Connection::class);

        $searchAdapter = $this->mockAdapter(['removeEntry']);
        $searchAdapter
            ->expects($this->once())
            ->method('removeEntry')
            ->with('https://example.com', $connection)
        ;

        $framework = $this->mockContaoFramework([Search::class => $searchAdapter]);
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $indexer = new DefaultIndexer($framework, $connection);
        $indexer->delete(new Document(new Uri('https://example.com'), 200, [], ''));
    }

    public function testClearsTheSearchIndex(): void
    {
        $framework = $this->mockContaoFramework();

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(3))
            ->method('executeStatement')
            ->withConsecutive(
                ['TRUNCATE TABLE tl_search'],
                ['TRUNCATE TABLE tl_search_index'],
                ['TRUNCATE TABLE tl_search_term']
            )
        ;

        $indexer = new DefaultIndexer($framework, $connection);
        $indexer->clear();
    }
}

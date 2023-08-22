<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search;

use Contao\CoreBundle\Search\Document;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentTest extends TestCase
{
    public function testCreatesADocumentFromRequestAndResponse(): void
    {
        $request = Request::create('https://example.com/foo?bar=baz');
        $response = new Response('body', 200, ['content-type' => ['text/html']]);
        $document = Document::createFromRequestResponse($request, $response);

        $this->assertSame('https://example.com/foo?bar=baz', (string) $document->getUri());
        $this->assertSame(200, $document->getStatusCode());

        $headers = $document->getHeaders();
        unset($headers['date']);

        $this->assertSame(['content-type' => ['text/html'], 'cache-control' => ['no-cache, private']], $headers);
    }

    /**
     * @dataProvider canonicalUriProvider
     */
    public function testExtractsTheCanonicalUri(string $body, array $headers, Uri|null $expectedCanonicalUri): void
    {
        $document = new Document(
            new Uri('https://example.com'),
            200,
            $headers,
            $body
        );

        if (!$expectedCanonicalUri) {
            $this->assertNull($document->extractCanonicalUri());
        } else {
            $this->assertSame((string) $expectedCanonicalUri, (string) $document->extractCanonicalUri());
        }
    }

    public function canonicalUriProvider(): \Generator
    {
        yield 'Test no data available' => [
            '',
            ['Content-Type' => ['text/html']],
            null,
        ];

        yield 'Test with Link header' => [
            '',
            ['Content-Type' => ['text/html'], 'Link' => ['Link: <https://www.example.com/foobar>; rel="canonical"']],
            new Uri('https://www.example.com/foobar'),
        ];

        yield 'Test with cononical link in body' => [
            '<html><head><link rel="canonical" href="https://www.example.com/foobar2" /></head><body></body></html>',
            ['Content-Type' => ['text/html']],
            new Uri('https://www.example.com/foobar2'),
        ];
    }

    /**
     * @dataProvider documentProvider
     */
    public function testExtractsTheJsdonLdScript(string $body, array $expectedJsonLds, string $context = 'https://contao.org/'): void
    {
        $document = new Document(
            new Uri('https://example.com'),
            200,
            ['Content-Type' => ['text/html']],
            $body
        );

        $this->assertSame('https://example.com', (string) $document->getUri());
        $this->assertSame(200, $document->getStatusCode());
        $this->assertSame(['content-type' => ['text/html']], $document->getHeaders());
        $this->assertSame($expectedJsonLds, $document->extractJsonLdScripts($context));
    }

    public function documentProvider(): \Generator
    {
        yield 'Test with empty body' => [
            '',
            [],
        ];

        yield 'Test with one valid json ld element' => [
            '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":true}</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
            ],
        ];

        yield 'Test with one valid json ld element without context' => [
            '<html><body><script type="application/ld+json">{"@type":"https:\/\/contao.org\/Page","https:\/\/contao.org\/foobar":true}</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
            ],
        ];

        yield 'Test with one valid json ld element with context' => [
            '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":true}</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
            ],
        ];

        yield 'Test with one valid json ld element with context prefix' => [
            '<html><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/contao.org\/"},"@type":"contao:Page","contao:foobar":true}</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
            ],
        ];

        yield 'Test with two valid json ld elements' => [
            '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":true}</script><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":false}</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
                [
                    '@type' => 'Page',
                    'foobar' => false,
                ],
            ],
        ];

        yield 'Test with two valid json ld elements combined in one script tag' => [
            '<html><body><script type="application/ld+json">[{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":true},{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":false}]</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
                [
                    '@type' => 'Page',
                    'foobar' => false,
                ],
            ],
        ];

        yield 'Test with two valid json ld elements combined in one script tag with @graph property' => [
            '<html><body><script type="application/ld+json">[{"@context":"https:\/\/contao.org\/","@graph":[{"@type":"Page","foobar":true}]},{"@context":"https:\/\/contao.org\/","@graph":[{"@type":"Page","foobar":false},{"@type":"Article","foobar":null}]}]</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
                [
                    '@type' => 'Page',
                    'foobar' => false,
                ],
                [
                    '@type' => 'Article',
                    'foobar' => null,
                ],
            ],
        ];

        yield 'Test with two valid json ld elements combined in one script tag and one extra json ld element in a separate script tag' => [
            '<html><body><script type="application/ld+json">[{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":true},{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":false}]</script><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":null}</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
                [
                    '@type' => 'Page',
                    'foobar' => false,
                ],
                [
                    '@type' => 'Page',
                    'foobar' => null,
                ],
            ],
        ];

        yield 'Test with one valid and one invalid json ld element' => [
            '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":true}</script><script type="application/ld+json">{"@context":"https:\/\/contao.org\/", ...</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
            ],
        ];

        yield 'Test with context without trailing slash' => [
            '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.org","@type":"WebPage","name":"Foobar"}</script></body></html>',
            [
                [
                    '@type' => 'WebPage',
                    'name' => 'Foobar',
                ],
            ],
            'https://schema.org',
        ];

        yield 'Test with no context filter provided' => [
            '<html><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@type":"Page","title":"Welcome to the official Contao Demo Site","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>',
            [
                [
                    '@context' => 'https://schema.contao.org/',
                    '@type' => 'https://schema.contao.org/Page',
                    'https://schema.contao.org/title' => 'Welcome to the official Contao Demo Site',
                    'https://schema.contao.org/pageId' => 2,
                    'https://schema.contao.org/noSearch' => false,
                    'https://schema.contao.org/protected' => false,
                    'https://schema.contao.org/groups' => [],
                    'https://schema.contao.org/fePreview' => false,
                ],
            ],
            '',
        ];

        yield 'Test with no context filter provided prefix context' => [
            '<html><body><script type="application/ld+json">{"@context":{"contao":"https:\/\/schema.contao.org\/"},"@type":"contao:Page","contao:title":"Welcome to the official Contao Demo Site","contao:pageId":2,"contao:noSearch":false,"contao:protected":false,"contao:groups":[],"contao:fePreview":false}</script></body></html>',
            [
                [
                    '@context' => [
                        'contao' => 'https://schema.contao.org/',
                    ],
                    '@type' => 'https://schema.contao.org/Page',
                    'https://schema.contao.org/title' => 'Welcome to the official Contao Demo Site',
                    'https://schema.contao.org/pageId' => 2,
                    'https://schema.contao.org/noSearch' => false,
                    'https://schema.contao.org/protected' => false,
                    'https://schema.contao.org/groups' => [],
                    'https://schema.contao.org/fePreview' => false,
                ],
            ],
            '',
        ];
    }

    public function testDoesNotExtractTheJsdonLdScriptIfTheContextOrTypeDoesNotMatch(): void
    {
        $document = new Document(
            new Uri('https://example.com'),
            200,
            ['Content-Type' => ['text/html']],
            '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":true}</script></body></html>'
        );

        $this->assertSame([], $document->extractJsonLdScripts('https://example.com/'));
        $this->assertSame([], $document->extractJsonLdScripts('https://contao.org/', 'nonsense-type'));
    }
}

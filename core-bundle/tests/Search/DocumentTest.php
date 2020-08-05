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
        $request = Request::create('https://example.com/foo?bar=baz', 'GET');
        $response = new Response('body', 200, ['content-type' => ['text/html']]);
        $document = Document::createFromRequestResponse($request, $response);

        $this->assertSame('https://example.com/foo?bar=baz', (string) $document->getUri());
        $this->assertSame(200, $document->getStatusCode());

        $headers = $document->getHeaders();
        unset($headers['date']);

        $this->assertSame(['content-type' => ['text/html'], 'cache-control' => ['no-cache, private']], $headers);
    }

    /**
     * @dataProvider documentProvider
     */
    public function testExtractsTheJsdonLdScript(string $body, array $expectedJsonLds): void
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
        $this->assertSame($expectedJsonLds, $document->extractJsonLdScripts('https://contao.org/'));
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

        yield 'Test with one valid and one invalid json ld element' => [
            '<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","foobar":true}</script><script type="application/ld+json">{"@context":"https:\/\/contao.org\/", ...</script></body></html>',
            [
                [
                    '@type' => 'Page',
                    'foobar' => true,
                ],
            ],
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

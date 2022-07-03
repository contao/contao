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

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SearchIndexListenerTest extends TestCase
{
    /**
     * @dataProvider getRequestResponse
     */
    public function testIndexesOrDeletesTheDocument(Request $request, Response $response, int $features, bool $index, bool $delete): void
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($index ? $this->once() : $this->never())
            ->method('index')
            ->with($this->isInstanceOf(Document::class))
        ;

        $indexer
            ->expects($delete ? $this->once() : $this->never())
            ->method('delete')
            ->with($this->isInstanceOf(Document::class))
        ;

        $event = new TerminateEvent($this->createMock(HttpKernelInterface::class), $request, $response);

        $listener = new SearchIndexListener($indexer, '_fragment', $features);
        $listener($event);
    }

    public function getRequestResponse(): \Generator
    {
        yield 'Should index because the response was successful and contains ld+json information' => [
            Request::create('/foobar'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            true,
            false,
        ];

        yield 'Should not index because even though the response was successful and contains ld+json information, it was disabled by the feature flag' => [
            Request::create('/foobar'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            SearchIndexListener::FEATURE_DELETE,
            false,
            false,
        ];

        yield 'Should be skipped because it is not a GET request' => [
            Request::create('/foobar', 'POST'),
            new Response(),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be skipped because it was requested by our own crawler' => [
            Request::create('/foobar', 'GET', [], [], [], ['HTTP_USER_AGENT' => Factory::USER_AGENT]),
            new Response(),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be skipped because it was a redirect' => [
            Request::create('/foobar'),
            new RedirectResponse('https://somewhere.else'),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be skipped because it is a fragment request' => [
            Request::create('_fragment/foo/bar'),
            new Response(),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be deleted because the response was not successful (404)' => [
            Request::create('/foobar'),
            new Response('', 404),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];

        yield 'Should be deleted because the response was not successful (403)' => [
            Request::create('/foobar'),
            new Response('', 403),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];

        yield 'Should not be deleted because even though the response was not successful (403), it was disabled by the feature flag ' => [
            Request::create('/foobar'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>', 403),
            SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        $response = new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>', 403);
        $response->headers->set('X-Robots-Tag', 'noindex');

        yield 'Should not be handled because the X-Robots-Tag header contains "noindex" ' => [
            Request::create('/foobar'),
            $response,
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];

        yield 'Should not be handled because the meta robots tag contains "noindex" ' => [
            Request::create('/foobar'),
            new Response('<html><head><meta name="robots" content="noindex,nofollow"/></head><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>', 403),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];
    }
}

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

use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SearchIndexListenerTest extends TestCase
{
    /**
     * @dataProvider getRequestResponse
     */
    public function testIndexesOrDeletesTheDocument(Request $request, Response $response, bool $index, bool $delete): void
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

        $listener = new SearchIndexListener($indexer);
        $listener->onKernelTerminate($event);
    }

    public function getRequestResponse(): \Generator
    {
        yield 'Should index because the response was successful and contains ld+json information' => [
            Request::create('/foobar'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            true,
            false,
        ];

        yield 'Should be skipped because it is not a GET request' => [
            Request::create('/foobar', 'POST'),
            new Response(),
            false,
            false,
        ];

        yield 'Should be skipped because it is a fragment request' => [
            Request::create('_fragment/foo/bar'),
            new Response(),
            false,
            false,
        ];

        yield 'Should be ignored because the response was not successful (404) but there was no ld+json data' => [
            Request::create('/foobar', 'GET'),
            new Response('', 404),
            false,
            false,
        ];

        yield 'Should be deleted because the response was not successful (404)' => [
            Request::create('/foobar', 'GET'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>', 404),
            false,
            true,
        ];

        yield 'Should be deleted because the response was not successful (403)' => [
            Request::create('/foobar', 'GET'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>', 403),
            false,
            true,
        ];
    }
}

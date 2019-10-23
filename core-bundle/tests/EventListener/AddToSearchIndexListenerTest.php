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

use Contao\CoreBundle\EventListener\AddToSearchIndexListener;
use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AddToSearchIndexListenerTest extends TestCase
{
    /**
     * @dataProvider getRequestResponse
     */
    public function testIndexesTheResponse(Request $request, Response $response, bool $index): void
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($index ? $this->once() : $this->never())
            ->method('index')
            ->with($this->isInstanceOf(Document::class))
        ;

        $event = new TerminateEvent($this->createMock(HttpKernelInterface::class), $request, $response);

        $listener = new AddToSearchIndexListener($indexer);
        $listener->onKernelTerminate($event);
    }

    public function getRequestResponse(): \Generator
    {
        yield [
            Request::create('/foobar'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            true,
        ];

        yield [
            Request::create('/foobar', 'POST'),
            new Response(),
            false,
        ];

        yield [
            Request::create('_fragment/foo/bar'),
            new Response(),
            false,
        ];
    }
}

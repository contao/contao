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
    public function testIndexesTheResponse(): void
    {
        $indexer = $this->createMock(IndexerInterface::class);

        $indexer
            ->expects($this->once())
            ->method('index')
            ->with($this->isInstanceOf(Document::class))
        ;

        $request = Request::create('/foobar');
        $response = new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"PageMetaData","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>');

        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $response
        );

        $listener = new AddToSearchIndexListener($indexer);
        $listener->onKernelTerminate($event);
    }

    public function testDoesNotIndexTheResponseIfTheRequestMethodIsNotGet(): void
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($this->never())
            ->method('index')
        ;

        $request = Request::create('/foobar', 'POST');
        $response = new Response();

        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $response
        );

        $listener = new AddToSearchIndexListener($indexer);
        $listener->onKernelTerminate($event);
    }

    public function testDoesNotIndexTheResponseUponFragmentRequests(): void
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($this->never())
            ->method('index')
        ;

        $request = Request::create('_fragment/foo/bar');
        $response = new Response();

        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $response
        );

        $listener = new AddToSearchIndexListener($indexer);
        $listener->onKernelTerminate($event);
    }
}

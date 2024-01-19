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

use Contao\CoreBundle\EventListener\PageTrailCacheTagsListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use FOS\HttpCache\ResponseTagger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class PageTrailCacheTagsListenerTest extends TestCase
{
    public function testDoesNotFailIfNoResponseTaggerAvailable(): void
    {
        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $this->createRequestWithPageModel([42, 18]),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $listener = new PageTrailCacheTagsListener($this->createScopeMatcher(true), null);
        $listener($event);

        // Increase the assertion count. If the test would fail, we'd get a
        // "Call to a member function addTags() on null" PHP error.
        $this->addToAssertionCount(1);
    }

    public function testDoesNotTagIfItIsNotAFrontendMasterRequest(): void
    {
        $responseTagger = $this->createMock(ResponseTagger::class);
        $responseTagger
            ->expects($this->never())
            ->method('addTags')
        ;

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $this->createRequestWithPageModel([14, 7]),
            HttpKernelInterface::SUB_REQUEST,
            new Response(),
        );

        $listener = new PageTrailCacheTagsListener($this->createScopeMatcher(false), $responseTagger);
        $listener($event);
    }

    public function testTagsCorrectly(): void
    {
        $responseTagger = $this->createMock(ResponseTagger::class);
        $responseTagger
            ->expects($this->once())
            ->method('addTags')
            ->with(['contao.db.tl_page.42', 'contao.db.tl_page.18'])
        ;

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $this->createRequestWithPageModel([42, 18]),
            HttpKernelInterface::SUB_REQUEST,
            new Response(),
        );

        $listener = new PageTrailCacheTagsListener($this->createScopeMatcher(true), $responseTagger);
        $listener($event);
    }

    private function createScopeMatcher(bool $isFrontendMainRequest): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isFrontendMainRequest')
            ->willReturn($isFrontendMainRequest)
        ;

        return $scopeMatcher;
    }

    private function createRequestWithPageModel(array $trail): Request
    {
        $request = new Request();
        $request->attributes->set('pageModel', $this->mockClassWithProperties(PageModel::class, ['trail' => $trail]));

        return $request;
    }
}

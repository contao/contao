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

use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

class SubrequestCacheSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $subscriber = new SubrequestCacheSubscriber();

        $this->assertSame(
            [
                KernelEvents::REQUEST => ['onKernelRequest', 255],
                KernelEvents::RESPONSE => ['onKernelResponse', -255],
            ],
            $subscriber::getSubscribedEvents()
        );
    }

    public function testMergesCacheControlHeader(): void
    {
        $subscriber = new SubrequestCacheSubscriber();

        $this->onKernelRequest($subscriber);

        $subResponse = new Response();
        $subResponse->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $subResponse->setPublic();
        $subResponse->setMaxAge(30);

        $this->onKernelResponse($subscriber, $subResponse, KernelInterface::SUB_REQUEST);

        $mainResponse = new Response();
        $mainResponse->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $mainResponse->setPublic();
        $mainResponse->setMaxAge(60);

        $this->onKernelResponse($subscriber, $mainResponse, KernelInterface::MASTER_REQUEST);

        $this->assertSame(30, $mainResponse->getMaxAge());
        $this->assertSame('max-age=30, public', $mainResponse->headers->get('Cache-Control'));
        $this->assertFalse($mainResponse->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));
    }

    public function testMakeMasterResponsePrivateIfSubrequestIsPrivate(): void
    {
        $subscriber = new SubrequestCacheSubscriber();

        $this->onKernelRequest($subscriber);

        $subResponse = new Response();
        $subResponse->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $subResponse->setPrivate();

        $this->onKernelResponse($subscriber, $subResponse, KernelInterface::SUB_REQUEST);

        $mainResponse = new Response();
        $mainResponse->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $subResponse->setPublic();
        $mainResponse->setMaxAge(60);

        $this->onKernelResponse($subscriber, $mainResponse, KernelInterface::MASTER_REQUEST);

        $this->assertSame('private', $mainResponse->headers->get('Cache-Control'));
        $this->assertFalse($mainResponse->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));
    }

    public function testIgnoresSubrequestWithoutMergeHeader(): void
    {
        $subscriber = new SubrequestCacheSubscriber();

        $this->onKernelRequest($subscriber);

        $subResponse = new Response();
        $subResponse->setPrivate();

        $this->onKernelResponse($subscriber, $subResponse, KernelInterface::SUB_REQUEST);

        $mainResponse = new Response();
        $mainResponse->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $mainResponse->setPublic();
        $mainResponse->setMaxAge(60);

        $this->onKernelResponse($subscriber, $mainResponse, KernelInterface::MASTER_REQUEST);

        $this->assertSame('max-age=60, public', $mainResponse->headers->get('Cache-Control'));
        $this->assertFalse($mainResponse->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));
    }

    public function testIgnoresSubrequestWithoutCacheControlHeader(): void
    {
        $subscriber = new SubrequestCacheSubscriber();

        $this->onKernelRequest($subscriber);

        $subResponse = new Response();
        $subResponse->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $subResponse->headers->remove('Cache-Control');

        $this->onKernelResponse($subscriber, $subResponse, KernelInterface::SUB_REQUEST);

        $mainResponse = new Response();
        $mainResponse->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $mainResponse->setPublic();
        $mainResponse->setMaxAge(60);

        $this->onKernelResponse($subscriber, $mainResponse, KernelInterface::MASTER_REQUEST);

        $this->assertSame('max-age=60, public', $mainResponse->headers->get('Cache-Control'));
        $this->assertFalse($mainResponse->headers->has(SubrequestCacheSubscriber::MERGE_CACHE_HEADER));
    }

    private function onKernelRequest(SubrequestCacheSubscriber $subscriber): void
    {
        $event = new RequestEvent($this->createMock(Kernel::class), new Request(), KernelInterface::MASTER_REQUEST);
        $subscriber->onKernelRequest($event);
    }

    private function onKernelResponse(SubrequestCacheSubscriber $subscriber, Response $response, int $requestType): void
    {
        $event = new ResponseEvent($this->createMock(Kernel::class), new Request(), $requestType, $response);
        $subscriber->onKernelResponse($event);
    }
}

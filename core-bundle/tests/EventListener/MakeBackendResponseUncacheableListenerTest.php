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

use Contao\CoreBundle\EventListener\MakeBackendResponseUncacheableListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MakeBackendResponseUncacheableListenerTest extends TestCase
{
    public function testIgnoresNonMainRequests(): void
    {
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::SUB_REQUEST,
            $response,
        );

        (new MakeBackendResponseUncacheableListener($this->createScopeMatcher(false)))($event);

        $this->assertFalse($response->headers->hasCacheControlDirective('no-store'));
    }

    public function testIgnoresNonContaoBackendMainRequests(): void
    {
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        (new MakeBackendResponseUncacheableListener($this->createScopeMatcher(false)))($event);

        $this->assertFalse($response->headers->hasCacheControlDirective('no-store'));
    }

    public function testMakesResponseUncacheableForMainBackendRequest(): void
    {
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );

        (new MakeBackendResponseUncacheableListener($this->createScopeMatcher(true)))($event);

        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    private function createScopeMatcher(bool $isBackendMainRequest): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendMainRequest')
            ->willReturn($isBackendMainRequest)
        ;

        return $scopeMatcher;
    }
}

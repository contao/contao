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

use Contao\CoreBundle\EventListener\TransportSecurityHeaderListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class TransportSecurityHeaderListenerTest extends TestCase
{
    public function testIgnoresNonContaoMainRequests(): void
    {
        $response = new Response();
        $request = Request::create('https://contao.org');

        $listener = new TransportSecurityHeaderListener($this->createScopeMatcher(false), 31536000);
        $listener($this->createEvent($request, $response));

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function testIgnoresContaoMainRequestsThatAreNotSecure(): void
    {
        $response = new Response();
        $request = Request::create('http://contao.org'); // This is the key for this test

        $listener = new TransportSecurityHeaderListener($this->createScopeMatcher(true), 31536000);
        $listener($this->createEvent($request, $response));

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function testAppliesCorrectTtl(): void
    {
        $response = new Response();
        $request = Request::create('https://contao.org');

        $listener = new TransportSecurityHeaderListener($this->createScopeMatcher(true), 31536000);
        $listener($this->createEvent($request, $response));

        $this->assertTrue($response->headers->has('Strict-Transport-Security'));
        $this->assertSame('max-age=31536000', $response->headers->get('Strict-Transport-Security'));

        $listener = new TransportSecurityHeaderListener($this->createScopeMatcher(true), 500);
        $listener($this->createEvent($request, $response));

        $this->assertTrue($response->headers->has('Strict-Transport-Security'));
        $this->assertSame('max-age=500', $response->headers->get('Strict-Transport-Security'));
    }

    private function createEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
    }

    private function createScopeMatcher(bool $isContaoMainRequest): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isContaoMainRequest')
            ->willReturn($isContaoMainRequest)
        ;

        return $scopeMatcher;
    }
}

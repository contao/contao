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

use Contao\CoreBundle\EventListener\BackendNoindexListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class BackendNoindexListenerTest extends TestCase
{
    public function testAddsNoindexToBackendResponse(): void
    {
        $request = Request::create('/contao');
        $request->attributes->set('_scope', 'backend');

        $response = new Response();
        $kernel = $this->createMock(KernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $listener = new BackendNoindexListener($this->getScopeMatcher());
        $listener($event);

        $this->assertSame('noindex', $response->headers->get('X-Robots-Tag'));
    }

    public function testDoesNotAddNoindexIfNotBackendResponse(): void
    {
        $request = Request::create('/foobar');
        $response = new Response();
        $kernel = $this->createMock(KernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $listener = new BackendNoindexListener($this->getScopeMatcher());
        $listener($event);

        $this->assertNull($response->headers->get('X-Robots-Tag'));
    }

    private function getScopeMatcher(): ScopeMatcher
    {
        $frontendMatcher = new RequestMatcher();
        $frontendMatcher->matchAttribute('_scope', 'frontend');

        $backendMatcher = new RequestMatcher();
        $backendMatcher->matchAttribute('_scope', 'backend');

        return new ScopeMatcher($backendMatcher, $frontendMatcher);
    }
}

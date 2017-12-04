<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\InsecureInstallationListener;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class InsecureInstallationListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new InsecureInstallationListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\InsecureInstallationListener', $listener);
    }

    public function testThrowsAnExceptionIfTheDocumentRootIsInsecure(): void
    {
        $listener = new InsecureInstallationListener();

        $this->expectException(InsecureInstallationException::class);

        $listener->onKernelRequest($this->mockResponseEvent($this->getRequest()));
    }

    public function testDoesNotThrowAnExceptionIfTheDocumentRootIsSecure(): void
    {
        $request = $this->getRequest();
        $request->server->set('REQUEST_URI', '/app_dev.php?do=test');
        $request->server->set('SCRIPT_FILENAME', $this->getTempDir().'/app_dev.php');

        $listener = new InsecureInstallationListener();
        $listener->onKernelRequest($this->mockResponseEvent($request));

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    public function testDoesNotThrowAnExceptionOnLocalhost(): void
    {
        $request = $this->getRequest();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $listener = new InsecureInstallationListener();
        $listener->onKernelRequest($this->mockResponseEvent($request));

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * Returns a request.
     *
     * @return Request
     */
    private function getRequest(): Request
    {
        $request = new Request();

        $request->server->set('SCRIPT_NAME', 'app_dev.php');
        $request->server->set('SCRIPT_FILENAME', $this->getTempDir().'/web/app_dev.php');
        $request->server->set('REMOTE_ADDR', '123.456.789.0');
        $request->server->set('REQUEST_URI', '/web/app_dev.php?do=test');

        return $request;
    }

    /**
     * Mocks a response event.
     *
     * @param Request|null $request
     *
     * @return GetResponseEvent
     */
    private function mockResponseEvent(Request $request = null): GetResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
        }

        return new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
    }
}

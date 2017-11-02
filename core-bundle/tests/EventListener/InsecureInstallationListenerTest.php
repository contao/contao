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
use Symfony\Component\HttpKernel\Kernel;
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
        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $this->getRequest(), Kernel::MASTER_REQUEST);

        $this->expectException(InsecureInstallationException::class);

        $listener = new InsecureInstallationListener();
        $listener->onKernelRequest($event);
    }

    public function testDoesNotThrowAnExceptionIfTheDocumentRootIsSecure(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = $this->getRequest();
        $request->server->set('REQUEST_URI', '/app_dev.php?do=test');
        $request->server->set('SCRIPT_FILENAME', $this->getRootDir().'/app_dev.php');

        $event = new GetResponseEvent($kernel, $request, Kernel::MASTER_REQUEST);

        $listener = new InsecureInstallationListener();
        $listener->onKernelRequest($event);

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    public function testDoesNotThrowAnExceptionOnLocalhost(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = $this->getRequest();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $event = new GetResponseEvent($kernel, $request, Kernel::MASTER_REQUEST);

        $listener = new InsecureInstallationListener();
        $listener->onKernelRequest($event);

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
        $request->server->set('SCRIPT_FILENAME', $this->getRootDir().'/web/app_dev.php');
        $request->server->set('REMOTE_ADDR', '123.456.789.0');
        $request->server->set('REQUEST_URI', '/web/app_dev.php?do=test');

        return $request;
    }
}

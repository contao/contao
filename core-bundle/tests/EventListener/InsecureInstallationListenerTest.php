<?php

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

/**
 * Tests the InsecureInstallationListener class.
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsecureInstallationListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new InsecureInstallationListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\InsecureInstallationListener', $listener);
    }

    /**
     * Tests the onKernelRequest() method.
     */
    public function testOnKernelRequest()
    {
        $kernel = $this->mockKernel();
        $event = new GetResponseEvent($kernel, $this->getRequestObject(), Kernel::MASTER_REQUEST);

        $this->expectException(InsecureInstallationException::class);

        $listener = new InsecureInstallationListener();
        $listener->onKernelRequest($event);
    }

    /**
     * Tests the onKernelRequest() method on localhost.
     */
    public function testOnKernelRequestOnLocalhost()
    {
        $kernel = $this->mockKernel();

        $request = $this->getRequestObject();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $event = new GetResponseEvent($kernel, $request, Kernel::MASTER_REQUEST);

        $listener = new InsecureInstallationListener();
        $listener->onKernelRequest($event);
    }

    /**
     * Tests the onKernelRequest() method with a secure document root.
     */
    public function testOnKernelRequestWithSecureDocumentRoot()
    {
        $kernel = $this->mockKernel();

        $request = $this->getRequestObject();
        $request->server->set('REQUEST_URI', '/app_dev.php?do=test');
        $request->server->set('SCRIPT_FILENAME', $this->getRootDir().'/app_dev.php');

        $event = new GetResponseEvent($kernel, $request, Kernel::MASTER_REQUEST);

        $listener = new InsecureInstallationListener();
        $listener->onKernelRequest($event);
    }

    /**
     * Returns a request object.
     *
     * @return Request
     */
    private function getRequestObject()
    {
        $request = new Request();

        $request->server->set('SCRIPT_NAME', 'app_dev.php');
        $request->server->set('SCRIPT_FILENAME', $this->getRootDir().'/web/app_dev.php');
        $request->server->set('REMOTE_ADDR', '123.456.789.0');
        $request->server->set('REQUEST_URI', '/web/app_dev.php?do=test');

        return $request;
    }
}

<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\AddToSearchIndexListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

require_once __DIR__ . '/../Fixtures/EventListener/Frontend.php';

/**
 * Tests the AddToSearchIndexListener class.
 *
 * @author Leo Feyer <https://contao.org>
 */
class AddToSearchIndexListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new AddToSearchIndexListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\AddToSearchIndexListener', $listener);
    }

    /**
     * Tests adding a response to the event.
     */
    public function testOnKernelRequest()
    {
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request();
        $response = new Response();
        $event    = new PostResponseEvent($kernel, $request, $response);
        $listener = new AddToSearchIndexListener();

        $listener->onKernelTerminate($event);
    }
}

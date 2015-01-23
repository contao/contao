<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\OutputFromCacheListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

require_once __DIR__ . '/../Fixtures/EventListener/Frontend.php';

/**
 * Tests the OutputFromCacheListener class.
 *
 * @author Leo Feyer <https://contao.org>
 */
class OutputFromCacheListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new OutputFromCacheListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\OutputFromCacheListener', $listener);
    }

    /**
     * Tests adding a response to the event.
     */
    public function testOnKernelRequest()
    {
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request();
        $event    = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = new OutputFromCacheListener();

        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
    }
}

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

use Contao\CoreBundle\EventListener\ServiceUnavailableListener;
use Contao\CoreBundle\Exception\ServiceUnavailableException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ServiceUnavailableListenerTest extends TestCase
{
    public function testDoesNotHandleBackendRequest(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel
            ->expects($this->never())
            ->method('loadDetails')
        ;

        $request = new Request();
        $request->attributes->set('_scope', 'backend');
        $request->attributes->set('pageModel', $pageModel);

        $event = $this->mockEvent($request);

        $listener = new ServiceUnavailableListener($this->mockScopeMatcher());
        $listener($event);
    }

    public function testDoesNotHandleFrontendSubrequest(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel
            ->expects($this->never())
            ->method('loadDetails')
        ;

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->attributes->set('pageModel', $pageModel);

        $event = $this->mockEvent($request, false);

        $listener = new ServiceUnavailableListener($this->mockScopeMatcher());
        $listener($event);
    }

    public function testDoesNotThrowExceptionInPreviewEntryPoint(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel
            ->expects($this->never())
            ->method('loadDetails')
        ;

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->attributes->set('_preview', true);
        $request->attributes->set('pageModel', $pageModel);

        $event = $this->mockEvent($request);

        $listener = new ServiceUnavailableListener($this->mockScopeMatcher());
        $listener($event);
    }

    public function testDoesNotThrowExceptionIfRouteBypassesMaintenance(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel
            ->expects($this->never())
            ->method('loadDetails')
        ;

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->attributes->set('_bypass_maintenance', true);
        $request->attributes->set('pageModel', $pageModel);

        $event = $this->mockEvent($request);

        $listener = new ServiceUnavailableListener($this->mockScopeMatcher());
        $listener($event);
    }

    public function testDoesNotThrowExceptionWithoutPageModelInRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        $event = $this->mockEvent($request);

        $listener = new ServiceUnavailableListener($this->mockScopeMatcher());
        $listener($event);
    }

    public function testDoesNotThrowExceptionIfMaintenanceIsNotEnabled(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['maintenanceMode' => '']);
        $pageModel
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->attributes->set('pageModel', $pageModel);

        $event = $this->mockEvent($request);

        $listener = new ServiceUnavailableListener($this->mockScopeMatcher());
        $listener($event);
    }

    public function testThrowExceptionIfMaintenanceIsEnabled(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['maintenanceMode' => true]);
        $pageModel
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->attributes->set('pageModel', $pageModel);

        $event = $this->mockEvent($request);

        $this->expectException(ServiceUnavailableException::class);
        $this->expectExceptionMessage('This website is in maintenance mode.');

        $listener = new ServiceUnavailableListener($this->mockScopeMatcher());
        $listener($event);
    }

    private function mockEvent(Request $request, bool $isMainRequest = true): RequestEvent&MockObject
    {
        $event = $this->createMock(RequestEvent::class);
        $event
            ->method('isMainRequest')
            ->willReturn($isMainRequest)
        ;

        $event
            ->method('getRequest')
            ->willReturn($request)
        ;

        return $event;
    }
}

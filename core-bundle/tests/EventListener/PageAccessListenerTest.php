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

use Contao\CoreBundle\EventListener\PageAccessListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class PageAccessListenerTest extends TestCase
{
    public function testDoesNothingWithoutPageModelInRequest(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $request = new Request();

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener = new PageAccessListener($this->mockPageFinder(null), $security);
        $listener($event);
    }

    public function testDeniesAccessToProtectedPageIfMemberIsNotLoggedIn(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_MEMBER')
            ->willReturn(false)
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'id' => 42,
            'protected' => true,
            'groups' => [1, 2, 3],
        ]);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn(new Request())
        ;

        $this->expectException(InsufficientAuthenticationException::class);
        $this->expectExceptionMessage('Not authenticated');

        $listener = new PageAccessListener($this->mockPageFinder($pageModel), $security);
        $listener($event);
    }

    public function testDeniesAccessToProtectedPageIfMemberIsNotInGroup(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_MEMBER', null, true],
                [ContaoCorePermissions::MEMBER_IN_GROUPS, [1, 2, 3], false],
            ])
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'id' => 42,
            'protected' => true,
            'groups' => [1, 2, 3],
        ]);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn(new Request())
        ;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Member does not have access to page ID 42');

        $listener = new PageAccessListener($this->mockPageFinder($pageModel), $security);
        $listener($event);
    }

    public function testDeniesAccessToProtectedPageIfMemberIsNotGuest(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_MEMBER', null, false],
                [ContaoCorePermissions::MEMBER_IN_GROUPS, [-1, 1], false],
            ])
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'id' => 42,
            'protected' => true,
            'groups' => [-1, 1],
        ]);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn(new Request())
        ;

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Member does not have access to page ID 42');

        $listener = new PageAccessListener($this->mockPageFinder($pageModel), $security);
        $listener($event);
    }

    public function testGrantsAccessToProtectedPageIfMemberIsInGroup(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_MEMBER', null, true],
                [ContaoCorePermissions::MEMBER_IN_GROUPS, [1, 2, 3], true],
            ])
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'id' => 42,
            'protected' => true,
            'groups' => [1, 2, 3],
        ]);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn(new Request())
        ;

        $listener = new PageAccessListener($this->mockPageFinder($pageModel), $security);
        $listener($event);
    }

    public function testGrantsAccessToProtectedPageIfIsGuest(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_MEMBER', null, false],
                [ContaoCorePermissions::MEMBER_IN_GROUPS, [-1, 1], true],
            ])
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, [
            'id' => 42,
            'protected' => true,
            'groups' => [-1, 1],
        ]);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn(new Request())
        ;

        $listener = new PageAccessListener($this->mockPageFinder($pageModel), $security);
        $listener($event);
    }

    private function mockPageFinder(PageModel|null $pageModel): PageFinder&MockObject
    {
        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('getCurrentPage')
            ->willReturn($pageModel)
        ;

        return $pageFinder;
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\PurgeMemberRegistrationsListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\MemberModel;
use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PurgeMemberRegistrationsListenerTest extends TestCase
{
    public function testPurgesExpiredMemberRegistrations(): void
    {
        $member = $this->createMock(MemberModel::class);
        $member
            ->expects($this->once())
            ->method('delete')
        ;

        $memberModelAdapter = $this->mockAdapter(['findExpiredRegistrations', 'delete']);
        $memberModelAdapter
            ->expects($this->once())
            ->method('findExpiredRegistrations')
            ->willReturn(new Collection([$member], 'tl_member'))
        ;

        $requestStack = new RequestStack();
        $scopeMatcher = $this->createMock(ScopeMatcher::class);

        $framework = $this->mockContaoFramework([MemberModel::class => $memberModelAdapter]);

        (new PurgeMemberRegistrationsListener($requestStack, $scopeMatcher, $framework))();
    }

    public function testDoesNotPurgeExpiredMemberRegistrationsInBackEndAction(): void
    {
        $member = $this->createMock(MemberModel::class);
        $member
            ->expects($this->never())
            ->method('delete')
        ;

        $memberModelAdapter = $this->mockAdapter(['findExpiredRegistrations', 'delete']);
        $memberModelAdapter
            ->expects($this->never())
            ->method('findExpiredRegistrations')
        ;

        $request = new Request(['act' => 'edit']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $framework = $this->mockContaoFramework([MemberModel::class => $memberModelAdapter]);

        (new PurgeMemberRegistrationsListener($requestStack, $scopeMatcher, $framework))();
    }
}

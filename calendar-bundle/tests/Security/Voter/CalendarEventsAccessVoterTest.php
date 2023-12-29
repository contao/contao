<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\Security\Voter;

use Contao\CalendarBundle\Security\ContaoCalendarPermissions;
use Contao\CalendarBundle\Security\Voter\CalendarEventsAccessVoter;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CalendarEventsAccessVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(5))
            ->method('decide')
            ->withConsecutive(
                [$token, [ContaoCalendarPermissions::USER_CAN_ACCESS_MODULE]],
                [$token, [ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR], 42],
                [$token, [ContaoCalendarPermissions::USER_CAN_ACCESS_MODULE]],
                [$token, [ContaoCalendarPermissions::USER_CAN_ACCESS_MODULE]],
                [$token, [ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR], 42],
            )
            ->willReturnOnConsecutiveCalls(true, true, false, true, false)
        ;

        $voter = new CalendarEventsAccessVoter($accessDecisionManager);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_calendar_events'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_calendar'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(CalendarEventsAccessVoter::class));

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_calendar_events', ['pid' => 42]),
                ['whatever'],
            ),
        );

        // Permission granted, so abstain! Our voters either deny or abstain,
        // they must never grant access (see #6201).
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_calendar_events', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_calendar_events'],
            ),
        );

        // Permission denied on back end module
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_calendar_events', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_calendar_events'],
            ),
        );

        // Permission denied on calendar
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_calendar_events', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_calendar_events'],
            ),
        );
    }

    public function testDeniesUpdateActionToNewParent(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(3))
            ->method('decide')
            ->withConsecutive(
                [$token, [ContaoCalendarPermissions::USER_CAN_ACCESS_MODULE]],
                [$token, [ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR], 42],
                [$token, [ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR], 43],
            )
            ->willReturnOnConsecutiveCalls(true, true, false)
        ;

        $voter = new CalendarEventsAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new UpdateAction('tl_calendar_events', ['pid' => 42], ['pid' => 43]),
                [ContaoCorePermissions::DC_PREFIX.'tl_calendar_events'],
            ),
        );
    }
}

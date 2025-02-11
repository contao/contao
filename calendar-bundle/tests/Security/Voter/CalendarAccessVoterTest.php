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
use Contao\CalendarBundle\Security\Voter\CalendarAccessVoter;
use Contao\CoreBundle\Tests\Security\Voter\DataContainer\AbstractAccessVoterTestCase;

class CalendarAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['id' => 42],
            [
                [[ContaoCalendarPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['id' => 42],
            [
                [[ContaoCalendarPermissions::USER_CAN_ACCESS_MODULE], null, false],
            ],
            false,
        ];

        // Permission denied on calendar
        yield [
            ['id' => 42],
            [
                [[ContaoCalendarPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR], 42, false],
            ],
            false,
        ];
    }

    protected function getVoterClass(): string
    {
        return CalendarAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_calendar';
    }
}

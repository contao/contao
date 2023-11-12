<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Security\Voter;

use Contao\CalendarBundle\Security\ContaoCalendarPermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Symfony\Bundle\SecurityBundle\Security;

class CalendarAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly Security $security)
    {
    }

    protected function getTable(): string
    {
        return 'tl_calendar';
    }

    protected function isGranted(UpdateAction|CreateAction|ReadAction|DeleteAction $action): bool
    {
        return match (true) {
            $action instanceof CreateAction => $this->security->isGranted(ContaoCalendarPermissions::USER_CAN_CREATE_CALENDARS),
            $action instanceof ReadAction,
                $action instanceof UpdateAction => $this->security->isGranted(ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR, $action->getCurrentId()),
            $action instanceof DeleteAction => $this->security->isGranted(ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR, $action->getCurrentId())
                && $this->security->isGranted(ContaoCalendarPermissions::USER_CAN_DELETE_CALENDARS),
        };
    }
}

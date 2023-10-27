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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CalendarAccessVoter implements VoterInterface, CacheableVoterInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === ContaoCorePermissions::DC_PREFIX.'tl_calendar';
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, ReadAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $isGranted = match (true) {
                $subject instanceof CreateAction => $this->security->isGranted(ContaoCalendarPermissions::USER_CAN_CREATE_CALENDARS),
                $subject instanceof ReadAction,
                $subject instanceof UpdateAction => $this->security->isGranted(ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR, $subject->getCurrentId()),
                $subject instanceof DeleteAction => $this->security->isGranted(ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR, $subject->getCurrentId())
                    && $this->security->isGranted(ContaoCalendarPermissions::USER_CAN_DELETE_CALENDARS),
                default => false,
            };

            if (!$isGranted) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}

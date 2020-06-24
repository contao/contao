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

use Contao\CalendarModel;
use Contao\CoreBundle\Security\Voter\AbstractFrontendAccessVoter;
use Contao\FrontendUser;

class CalendarModelFrontendAccessVoter extends AbstractFrontendAccessVoter
{
    protected function supportsSubject($subject): bool
    {
        return $subject instanceof CalendarModel;
    }

    /**
     * @param CalendarModel $subject
     */
    protected function voteOnSubject($subject, ?FrontendUser $user): bool
    {
        if (!$subject->protected) {
            return true;
        }

        return $this->userHasGroups($user, $subject->groups);
    }
}

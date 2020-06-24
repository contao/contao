<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Security\Voter;

use Contao\CoreBundle\Security\Voter\AbstractFrontendAccessVoter;
use Contao\FrontendUser;
use Contao\NewsArchiveModel;

class NewsArchiveFrontendAccessVoter extends AbstractFrontendAccessVoter
{
    protected function supportsSubject($subject): bool
    {
        return $subject instanceof NewsArchiveModel;
    }

    /**
     * @param NewsArchiveModel $subject
     */
    protected function voteOnSubject($subject, ?FrontendUser $user): bool
    {
        if (!$subject->protected) {
            return true;
        }

        return $this->userHasGroups($user, $subject->groups);
    }
}

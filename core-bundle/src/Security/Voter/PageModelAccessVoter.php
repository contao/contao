<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter;

use Contao\FrontendUser;
use Contao\PageModel;

class PageModelAccessVoter extends AbstractFrontendAccessVoter
{
    protected function supportsSubject($subject): bool
    {
        return $subject instanceof PageModel;
    }

    /**
     * @param PageModel $pageModel
     */
    protected function voteOnSubject($pageModel, ?FrontendUser $user): bool
    {
        if (!$pageModel->protected) {
            return true;
        }

        return $this->userHasGroups($user, $pageModel->groups);
    }
}

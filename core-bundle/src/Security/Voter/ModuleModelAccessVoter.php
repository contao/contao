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
use Contao\ModuleModel;

class ModuleModelAccessVoter extends AbstractFrontendAccessVoter
{
    protected function supportsSubject($subject): bool
    {
        return $subject instanceof ModuleModel;
    }

    /**
     * @param ModuleModel $moduleModel
     */
    protected function voteOnSubject($moduleModel, ?FrontendUser $user): bool
    {
        if ($moduleModel->guests && null !== $user) {
            return false;
        }

        if (!$moduleModel->protected) {
            return true;
        }

        return $this->userHasGroups($user, $moduleModel->groups);
    }
}

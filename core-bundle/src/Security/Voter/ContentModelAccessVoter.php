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

use Contao\ContentModel;
use Contao\FrontendUser;

class ContentModelAccessVoter extends AbstractFrontendAccessVoter
{
    protected function supportsSubject($subject): bool
    {
        return $subject instanceof ContentModel;
    }

    /**
     * @param ContentModel $contentModel
     */
    protected function voteOnSubject($contentModel, ?FrontendUser $user): bool
    {
        if ($contentModel->guests && null !== $user) {
            return false;
        }

        if (!$contentModel->protected) {
            return true;
        }

        return $this->userHasGroups($user, $contentModel->groups);
    }
}

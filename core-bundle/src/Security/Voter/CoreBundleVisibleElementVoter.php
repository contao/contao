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

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\FrontendUser;
use Contao\ModuleModel;

class CoreBundleVisibleElementVoter extends AbstractFrontendAccessVoter
{
    protected function supportsSubject($subject): bool
    {
        return $subject instanceof ContentModel
            || $subject instanceof ModuleModel
            || $subject instanceof ArticleModel;
    }

    /**
     * @param ContentModel $contentModel
     */
    protected function voteOnSubject($contentModel, ?FrontendUser $user): bool
    {
        return $this->isVisibleElement($contentModel, $user);
    }
}

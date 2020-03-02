<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Security;

use Contao\CoreBundle\Security\Authorization\DcaPermission;
use Contao\CoreBundle\Security\Voter\AbstractDcaVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class NewsArchiveAccessVoter extends AbstractDcaVoter
{
    protected function getTable(): string
    {
        return 'tl_news_archive';
    }

    /**
     * @param DcaPermission $subject
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // TODO: implement newsp settings
        $user = $this->getBackendUser($token);

        if (null === $user) {
            return false;
        }

        $allowedNewsArchives = array_map('intval', (array) $user->news);

        if (0 === \count($allowedNewsArchives)) {
            return true;
        }

        if ($this->isCollectionOperation($attribute)) {
            $newsArchiveId = (int) $subject->getId();

            if (0 === $newsArchiveId) {
                return true;
            }

            return \in_array($newsArchiveId, $allowedNewsArchives, true);
        }

        $newsArchiveId = (int) $subject->getId();

        if (0 === $newsArchiveId) {
            return false;
        }

        if ('create' === $attribute) {
            return $user->hasAccess('create', 'newp');
        }

        if ('delete' === $attribute || 'deleteAll' === $attribute) {
            return $user->hasAccess('delete', 'newp');
        }

        return \in_array($newsArchiveId, $allowedNewsArchives, true);
    }
}

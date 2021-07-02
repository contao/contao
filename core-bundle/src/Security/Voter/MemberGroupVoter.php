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

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\FrontendUser;
use Contao\StringUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MemberGroupVoter extends Voter
{
    protected function supports($attribute, $subject): bool
    {
        if (ContaoCorePermissions::MEMBER_IN_GROUPS !== $attribute) {
            return false;
        }

        return is_numeric($subject)
            || (\is_array($subject) && \count($subject) === \count(array_filter($subject, 'is_numeric')));
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // Filter non-numeric values
        $subject = array_filter(array_map('intval', (array) $subject));

        if (empty($subject)) {
            return false;
        }

        $user = $token->getUser();

        if (!$user instanceof FrontendUser) {
            return \in_array(-1, $subject, true);
        }

        $groups = StringUtil::deserialize($user->groups, true);

        // No groups assigned
        if (empty($groups)) {
            return false;
        }

        return \count(array_intersect($subject, $groups)) > 0;
    }
}

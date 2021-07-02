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
        $user = $token->getUser();
        $groups = array_map('intval', (array) $subject);

        if (!$user instanceof FrontendUser) {
            return \in_array(-1, $groups, true);
        }

        return $user->isMemberOf($subject);
    }
}

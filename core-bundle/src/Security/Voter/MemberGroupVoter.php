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
    protected function supports(string $attribute, mixed $subject): bool
    {
        return ContaoCorePermissions::MEMBER_IN_GROUPS === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!\is_array($subject)) {
            $subject = StringUtil::deserialize($subject, true);
        }

        // Filter non-numeric values
        $subject = array_filter($subject, static fn ($val) => (string) (int) $val === (string) $val);

        if (!$subject) {
            return false;
        }

        $user = $token->getUser();

        if (!$user instanceof FrontendUser) {
            return \in_array(-1, array_map('intval', $subject), true);
        }

        $groups = StringUtil::deserialize($user->groups, true);

        // No groups assigned
        if (empty($groups)) {
            return false;
        }

        return [] !== array_intersect($subject, $groups);
    }
}

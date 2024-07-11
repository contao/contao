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
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class MemberGroupVoter implements VoterInterface, CacheableVoterInterface
{
    public function supportsAttribute(string $attribute): bool
    {
        return ContaoCorePermissions::MEMBER_IN_GROUPS === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return true;
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if (!array_filter($attributes, $this->supportsAttribute(...))) {
            return self::ACCESS_ABSTAIN;
        }

        if (!\is_array($subject)) {
            $subject = StringUtil::deserialize($subject, true);
        }

        // Filter non-numeric values
        $subject = array_filter($subject, static fn ($val) => (string) (int) $val === (string) $val);

        if (!$subject) {
            return self::ACCESS_DENIED;
        }

        $user = $token->getUser();

        if (!$user instanceof FrontendUser || $token instanceof TwoFactorTokenInterface) {
            return \in_array(-1, array_map(\intval(...), $subject), true) ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
        }

        $groups = StringUtil::deserialize($user->groups, true);

        // No groups assigned
        if (empty($groups)) {
            return self::ACCESS_DENIED;
        }

        return [] !== array_intersect($subject, $groups) ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }
}

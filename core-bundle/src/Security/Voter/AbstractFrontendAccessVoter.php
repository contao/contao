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
use Contao\StringUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class AbstractFrontendAccessVoter extends Voter
{
    public const ATTRIBUTE = 'contao_frontend.access';

    protected function supports($attribute, $subject): bool
    {
        return self::ATTRIBUTE === $attribute && $this->supportsSubject($subject);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof FrontendUser || !\in_array('ROLE_MEMBER', $token->getRoleNames(), true)) {
            $user = null;
        }

        return $this->voteOnSubject($subject, $user);
    }

    abstract protected function supportsSubject($subject): bool;

    abstract protected function voteOnSubject($subject, ?FrontendUser $user): bool;

    protected function userHasGroups(?FrontendUser $user, $groups): bool
    {
        if (null === $user) {
            return false;
        }

        $groups = StringUtil::deserialize($groups);
        $userGroups = StringUtil::deserialize($user->groups);

        return !empty($groups)
            && \is_array($groups)
            && \is_array($userGroups)
            && \count(array_intersect($groups, $userGroups)) > 0;
    }
}

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

use Contao\BackendUser;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\FrontendUser;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, PersonalAccessToken>
 */
class PersonalAccessTokenVoter extends Voter
{
    public function supportsAttribute(string $attribute): bool
    {
        return ContaoCorePermissions::PERSONAL_ACCESS_TOKEN_OWNERSHIP === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, PersonalAccessToken::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute) && $this->supportsType($subject::class);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, Vote|null $vote = null): bool
    {
        if ($token instanceof TwoFactorTokenInterface) {
            return false;
        }

        $user = $token->getUser();

        if ($user instanceof BackendUser) {
            return ContaoCoreBundle::SCOPE_BACKEND === $subject->getScope() && $subject->getUserId() === (int) $user->id;
        }

        if ($user instanceof FrontendUser) {
            return ContaoCoreBundle::SCOPE_FRONTEND === $subject->getScope() && $subject->getUserId() === (int) $user->id;
        }

        return false;
    }
}

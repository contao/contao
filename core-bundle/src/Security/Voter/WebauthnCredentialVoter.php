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

use Contao\CoreBundle\Entity\WebauthnCredential;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\User;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class WebauthnCredentialVoter implements CacheableVoterInterface
{
    public function supportsAttribute(string $attribute): bool
    {
        return ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return WebauthnCredential::class === $subjectType;
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes, Vote|null $vote = null): int
    {
        if (!array_filter($attributes, $this->supportsAttribute(...))) {
            return self::ACCESS_ABSTAIN;
        }

        if (!$subject instanceof WebauthnCredential) {
            return self::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();

        if (!$user instanceof User || $token instanceof TwoFactorTokenInterface) {
            return self::ACCESS_DENIED;
        }

        if ($subject->userHandle === $user->getPasskeyUserHandle()) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }
}

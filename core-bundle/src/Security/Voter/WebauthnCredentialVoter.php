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
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, WebauthnCredential>
 */
class WebauthnCredentialVoter extends Voter
{
    public function supportsAttribute(string $attribute): bool
    {
        return ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return WebauthnCredential::class === $subjectType;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute) && $this->supportsType($subject::class);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User || $token instanceof TwoFactorTokenInterface) {
            return false;
        }

        return $subject->userHandle === $user->getPasskeyUserHandle();
    }
}

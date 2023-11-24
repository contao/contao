<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter\DataContainer;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;

/**
 * By default, the Contao back end is fully accessible unless a developer wants to have specific
 * permissions. That's why this voter is implemented with a very low priority, so it allows everything
 * in the back end as the last voter in case no other voter decided to deny access before.
 */
class DefaultDataContainerVoter implements CacheableVoterInterface
{
    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, ContaoCorePermissions::DC_PREFIX);
    }

    public function supportsType(string $subjectType): bool
    {
        return true;
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if ($this->supportsAttribute($attribute)) {
                return self::ACCESS_GRANTED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}

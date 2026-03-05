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

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\Palette;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 */
class UserProfileVoter implements CacheableVoterInterface
{
    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === ContaoCorePermissions::DC_PREFIX.'tl_user';
    }

    public function supportsType(string $subjectType): bool
    {
        return UpdateAction::class === $subjectType;
    }

    public function vote(TokenInterface $token, $subject, array $attributes, Vote|null $vote = null): int
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser || !$subject instanceof UpdateAction) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if ((int) $user->id !== (int) $subject->getCurrentId()) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // Allow UpdateAction without actual data
        if (null === $subject->getNew()) {
            return VoterInterface::ACCESS_GRANTED;
        }

        $palette = new Palette($GLOBALS['TL_DCA']['tl_user']['palettes']['login'] ?? '');
        $excluded = array_filter(array_keys($subject->getNew()), static fn (string $field) => 'tstamp' !== $field && !$palette->hasField($field));

        return [] === $excluded ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_ABSTAIN;
    }
}

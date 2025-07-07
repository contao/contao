<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Security\Voter;

use Contao\CommentsBundle\Security\ContaoCommentsPermissions;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class LegacyHookCommentsVoter implements VoterInterface, CacheableVoterInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }


    public function supportsAttribute(string $attribute): bool
    {
        return ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return 'array' === $subjectType;
    }

    public function vote(TokenInterface $token, $subject, array $attributes, Vote|null $vote = null): int
    {
        if (
            !isset($subject['source'], $subject['parent'])
            || !isset($GLOBALS['TL_HOOKS']['isAllowedToEditComment'])
            || !\is_array($GLOBALS['TL_HOOKS']['isAllowedToEditComment'])
            || !\is_array($subject)
            || !array_filter($attributes, $this->supportsAttribute(...))
        ) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        trigger_deprecation('contao/comments-bundle', '5.6', 'The isAllowedToEditComment hook is deprecated and will be removed in Contao 6. Implement a security voters based on AbstractCommentsVoter instead.');

        $systemAdapter = $this->framework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['isAllowedToEditComment'] as $callback) {
            if (true === $systemAdapter->importStatic($callback[0])->{$callback[1]}($subject['parent'], $subject['source'])) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }
}

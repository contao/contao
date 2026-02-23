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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Contracts\Service\ResetInterface;

abstract class AbstractCommentsVoter implements VoterInterface, CacheableVoterInterface, ResetInterface
{
    private array $cache = [];

    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    public function reset(): void
    {
        $this->cache = [];
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
            !\is_array($subject)
            || !isset($subject['source'], $subject['parent'])
            || !$this->supportsSource($subject['source'])
            || !array_filter($attributes, $this->supportsAttribute(...))
        ) {
            return self::ACCESS_ABSTAIN;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return self::ACCESS_GRANTED;
        }

        $cacheKey = $subject['source'].'.'.$subject['parent'];

        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->hasAccess($token, $subject['source'], (int) $subject['parent']);
        }

        return $this->cache[$cacheKey] ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
    }

    abstract protected function supportsSource(string $source): bool;

    abstract protected function hasAccess(TokenInterface $token, string $source, int $parent): bool;
}

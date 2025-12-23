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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CommentsAccessVoter implements VoterInterface, CacheableVoterInterface
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === ContaoCorePermissions::DC_PREFIX.'tl_comments';
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes, Vote|null $vote = null): int
    {
        if (!array_filter($attributes, $this->supportsAttribute(...))) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $isGranted = match (true) {
                $subject instanceof CreateAction => $this->accessDecisionManager->decide($token, [ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT], $subject->getNew()),
                $subject instanceof UpdateAction => $this->accessDecisionManager->decide($token, [ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT], [...$subject->getCurrent() ?? [], ...$subject->getNew() ?? []]),
                $subject instanceof DeleteAction => $this->accessDecisionManager->decide($token, [ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT], $subject->getCurrent()),
                default => null,
            };

            if (true === $isGranted) {
                return self::ACCESS_GRANTED;
            }
        }

        // TODO: in Contao 6, this should default to ACCESS_ABSTAIN if no voter denied
        return self::ACCESS_DENIED;
    }
}

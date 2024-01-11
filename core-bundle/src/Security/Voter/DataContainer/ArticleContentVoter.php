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
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @internal
 */
class ArticleContentVoter implements VoterInterface, CacheableVoterInterface
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return ContaoCorePermissions::DC_PREFIX.'tl_content' === $attribute;
    }

    public function supportsType(string $subjectType): bool
    {
        return ReadAction::class === $subjectType;
    }

    /**
     * This only implements read access permission on tl_content
     * to disable the "children" operation on tl_article. It should be extended
     * to also check all other permissions on tl_content.
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if (!$subject instanceof ReadAction || 'tl_article' !== ($subject->getCurrent()['ptable'] ?? null)) {
            return self::ACCESS_ABSTAIN;
        }

        if (!array_filter($attributes, $this->supportsAttribute(...))) {
            return self::ACCESS_ABSTAIN;
        }

        if (!$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_EDIT_ARTICLES], $subject->getCurrentPid())) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}

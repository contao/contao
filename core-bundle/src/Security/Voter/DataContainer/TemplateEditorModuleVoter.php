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
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\StringUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;

/**
 * @internal
 */
class TemplateEditorModuleVoter implements CacheableVoterInterface
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \in_array($attribute, [ContaoCorePermissions::DC_PREFIX.'tl_user', ContaoCorePermissions::DC_PREFIX.'tl_user_group'], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, UpdateAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if (!$subject instanceof CreateAction && !$subject instanceof UpdateAction) {
            return self::ACCESS_ABSTAIN;
        }

        if (
            !\in_array('tpl_editor', StringUtil::deserialize($subject->getNew()['modules'] ?? null, true), true)
            || (
                $subject instanceof UpdateAction
                && \in_array('tpl_editor', StringUtil::deserialize($subject->getCurrent()['modules'] ?? null, true), true)
            )
            || $this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])
        ) {
            return self::ACCESS_ABSTAIN;
        }

        return self::ACCESS_DENIED;
    }
}

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
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class CommentsAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function getTable(): string
    {
        return 'tl_comments';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($action instanceof ReadAction || $action instanceof CreateAction) {
            return true;
        }

        $comment = match (true) {
            $action instanceof CreateAction => $action->getNew() ?? [],
            $action instanceof DeleteAction => $action->getCurrent() ?? [],
            $action instanceof UpdateAction => array_merge($action->getCurrent() ?? [], $action->getNew() ?? [])
        };

        return $this->accessDecisionManager->decide($token, [ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT], $comment);
    }
}

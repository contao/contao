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
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class PreviewVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function getTable(): string
    {
        return 'tl_preview_link';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        $createdBy = (int) match (true) {
            $action instanceof ReadAction,
            $action instanceof UpdateAction,
            $action instanceof DeleteAction => $action->getCurrent()['createdBy'] ?? null,
            $action instanceof CreateAction => $action->getNew()['createdBy'] ?? null,
        };

        if ((int) $user->id !== $createdBy) {
            return false;
        }

        if (
            $action instanceof UpdateAction
            && isset($action->getNew()['createdBy'])
            && (int) $action->getNew()['createdBy'] !== (int) $user->id
        ) {
            return false;
        }

        return true;
    }
}

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

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

trait ParentAccessTrait
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    private function hasAccessToParent(TokenInterface $token, string $attribute, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $pids = [];

        $pids[] = match (true) {
            $action instanceof CreateAction => $action->getNewPid(),
            $action instanceof ReadAction,
            $action instanceof UpdateAction,
            $action instanceof DeleteAction => $action->getCurrentPid(),
        };

        if (
            $action instanceof UpdateAction
            && ($newPid = (int) $action->getNewPid())
            && $newPid !== (int) $action->getCurrentPid()
        ) {
            $pids[] = $newPid;
        }

        foreach ($pids as $pid) {
            if (!$this->accessDecisionManager->decide($token, [$attribute], $pid)) {
                return false;
            }
        }

        return true;
    }
}

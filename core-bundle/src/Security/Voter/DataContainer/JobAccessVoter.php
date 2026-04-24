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
class JobAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [ReadAction::class, DeleteAction::class], true);
    }

    protected function getTable(): string
    {
        return 'tl_job';
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

        // User can delete only his own jobs
        if ($action instanceof DeleteAction) {
            return (int) $action->getCurrent()['owner'] === (int) $user->id;
        }

        // User can read only his own jobs and public ones
        if ($action instanceof ReadAction) {
            $current = $action->getCurrent();

            return $current['public'] || (int) $current['owner'] === (int) $user->id;
        }

        return true;
    }
}

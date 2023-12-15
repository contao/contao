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
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
class FavoritesVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly Security $security)
    {
    }

    protected function getTable(): string
    {
        return 'tl_favorites';
    }

    protected function isGranted(CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        $userId = (int) $user->id;

        $canAccessCurrent = match (true) {
            $action instanceof UpdateAction,
            $action instanceof ReadAction,
            $action instanceof DeleteAction => (int) $action->getCurrent()['user'] === $userId,
            default => true,
        };

        $canAccessNew = match (true) {
            $action instanceof CreateAction,
            $action instanceof UpdateAction => !isset($action->getNew()['user']) || $action->getNew()['user'] === $userId,
            default => true,
        };

        return $canAccessCurrent && $canAccessNew;
    }
}

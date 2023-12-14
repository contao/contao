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
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
class FavoritesVoter extends AbstractDataContainerVoter
{
    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
    ) {
    }

    protected function getTable(): string
    {
        return 'tl_favorites';
    }

    protected function isGranted(CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        return match (true) {
            $action instanceof CreateAction => true,
            $action instanceof ReadAction,
            $action instanceof UpdateAction,
            $action instanceof DeleteAction => $this->checkAccess($action),
            default => false,
        };
    }

    private function checkAccess(DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $user = $this->security->getUser();
        $userId = $user instanceof BackendUser ? (int) $user->id : 0;

        $createdBy = (int) $this->connection->fetchOne(
            'SELECT user FROM tl_favorites WHERE id = :id',
            ['id' => $action->getCurrentId()],
        );

        return $createdBy === $userId;
    }
}

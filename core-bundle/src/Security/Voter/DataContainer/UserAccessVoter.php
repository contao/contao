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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
class UserAccessVoter extends AbstractDataContainerVoter implements ResetInterface
{
    /**
     * @var array<int>|null
     */
    private array|null $adminIds = null;

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
    }

    public function reset(): void
    {
        $this->adminIds = null;
    }

    protected function getTable(): string
    {
        return 'tl_user';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        $user = $token->getUser();
        $isCurrentUser = $user instanceof BackendUser
            && !$action instanceof CreateAction
            && (int) $user->id === (int) $action->getCurrentId();

        // Current user can change its profile regardless of user module access, but is
        // never allowed to delete or disable itself or change its admin state
        if ($isCurrentUser) {
            return $action instanceof ReadAction
                || (
                    $action instanceof UpdateAction
                    && !isset($action->getNew()['disable'])
                    && !isset($action->getNew()['admin'])
                );
        }

        if (!$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'user')) {
            return false;
        }

        if ($action instanceof ReadAction) {
            return true;
        }

        if ($this->accessDecisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        // Regular user is not allowed to create admin or update admin field
        if (
            ($action instanceof CreateAction && (1 === (int) ($action->getNew()['admin'] ?? 0)))
            || ($action instanceof UpdateAction && isset($action->getNew()['admin']))
        ) {
            return false;
        }

        if ($action instanceof CreateAction) {
            return true;
        }

        // Regular users cannot update or delete admin users
        return !\in_array((int) $action->getCurrentId(), $this->getAdminIds(), true);
    }

    private function getAdminIds(): array
    {
        if (null === $this->adminIds) {
            $this->adminIds = $this->connection->fetchFirstColumn('SELECT id FROM tl_user WHERE `admin`=1');
            $this->adminIds = array_map('intval', $this->adminIds);
        }

        return $this->adminIds;
    }
}

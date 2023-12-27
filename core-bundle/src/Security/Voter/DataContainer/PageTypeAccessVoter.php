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
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class PageTypeAccessVoter extends AbstractDataContainerVoter
{
    private const FIRST_LEVEL_TYPES = ['error_401', 'error_403', 'error_404', 'error_503'];

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
    }

    protected function getTable(): string
    {
        return 'tl_page';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        return $this->validateAccessToPageType($token, $action)
            && $this->validateFirstLevelType($action)
            && $this->validateRootType($action)
            && $this->validateRootNode($action);
    }

    private function validateAccessToPageType(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($action instanceof ReadAction) {
            return true;
        }

        $types = [];

        if (!$action instanceof CreateAction && isset($action->getCurrent()['type'])) {
            $types[] = $action->getCurrent()['type'];
        }

        if (!$action instanceof DeleteAction && isset($action->getNew()['type'])) {
            $types[] = $action->getNew()['type'];
        }

        if ([] === $types) {
            return true;
        }

        foreach ($types as $type) {
            if (!$this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE], $type)) {
                return false;
            }
        }

        return true;
    }

    private function validateFirstLevelType(CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($action instanceof ReadAction || $action instanceof DeleteAction) {
            return true;
        }

        // Allow if current page type is not an error page, or the new page type is not set to an error page type.
        if (
            (!$action instanceof UpdateAction || !\in_array($action->getCurrent()['type'], self::FIRST_LEVEL_TYPES, true))
            && (!isset($action->getNew()['type']) || !\in_array($action->getNew()['type'], self::FIRST_LEVEL_TYPES, true))
        ) {
            return true;
        }

        $type = $action->getNew()['type'] ?? ($action instanceof UpdateAction ? $action->getCurrent()['type'] : null);

        if (
            null !== $action->getNewPid()
            && null !== $action->getNew()['sorting']
            && (
                !$this->isRootPage((int) $action->getNewPid())
                || $this->hasPageTypeInRoot($type, (int) $action->getNewPid())
            )
        ) {
            return false;
        }

        if (
            $action instanceof UpdateAction
            && isset($action->getNew()['type'])
            && in_array($action->getNew()['type'], self::FIRST_LEVEL_TYPES, true)
            && (
                !$this->isRootPage((int) $action->getCurrentPid())
                || $this->hasPageTypeInRoot($type, (int) $action->getCurrentPid())
            )
        ) {
            return false;
        }

        return true;
    }

    private function validateRootType(CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($action instanceof ReadAction || $action instanceof DeleteAction) {
            return true;
        }

        if (
            (!$action instanceof UpdateAction || 'root' !== $action->getCurrent()['type'])
            && (!isset($action->getNew()['type']) || 'root' !== $action->getNew()['type'])
        ) {
            return true;
        }

        if ($action instanceof UpdateAction && 0 !== (int) $action->getCurrentPid()) {
            return false;
        }

        if (0 !== (int) $action->getNewPid()) {
            return false;
        }

        return true;
    }

    private function validateRootNode(CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($action instanceof ReadAction || $action instanceof DeleteAction) {
            return true;
        }

        if (
            (!$action instanceof UpdateAction || (int) $action->getCurrentPid() > 0)
            && (null === $action->getNewPid() || (int) $action->getNewPid() > 0)
        ) {
            return true;
        }

        if (
            ($action instanceof UpdateAction && 'root' !== $action->getCurrent()['type'])
            || (isset($action->getNew()['type']) && 'root' !== $action->getNew()['type'])
        ) {
            return false;
        }

        return true;
    }

    private function isRootPage(int $pageId): bool
    {
        return 'root' === $this->connection->fetchOne('SELECT type FROM tl_page WHERE id=?', [$pageId]);
    }

    private function hasPageTypeInRoot(string $type, int $rootId): bool
    {
        return (bool) $this->connection->fetchOne('SELECT id FROM tl_page WHERE type=? AND pid=?', [$type, $rootId]);
    }
}

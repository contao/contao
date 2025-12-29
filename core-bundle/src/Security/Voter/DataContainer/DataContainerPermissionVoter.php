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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class DataContainerPermissionVoter implements CacheableVoterInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, ContaoCorePermissions::DC_PREFIX);
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes, Vote|null $vote = null): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $table = $subject->getDataSource();

            $this->framework->getAdapter(Controller::class)->loadDataContainer($table);

            $canOperate = match (true) {
                $subject instanceof CreateAction => $this->canCreate($token, $table),
                $subject instanceof UpdateAction => $this->canUpdate($token, $table),
                $subject instanceof DeleteAction => $this->canDelete($token, $table),
                default => throw new \UnexpectedValueException(),
            };

            return $canOperate ? self::ACCESS_ABSTAIN : self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function canCreate(TokenInterface $token, string $table): bool
    {
        if (
            ($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? false)
            || ($GLOBALS['TL_DCA'][$table]['config']['notCreatable'] ?? false)
            || ($GLOBALS['TL_DCA'][$table]['config']['notEditable'] ?? false)
        ) {
            return false;
        }

        return $this->canOperate($token, $table, 'create');
    }

    private function canUpdate(TokenInterface $token, string $table): bool
    {
        if (
            ($GLOBALS['TL_DCA'][$table]['config']['closed'] ?? false)
            || ($GLOBALS['TL_DCA'][$table]['config']['notEditable'] ?? false)
        ) {
            return false;
        }

        return $this->canOperate($token, $table, 'update');
    }

    private function canDelete(TokenInterface $token, string $table): bool
    {
        if ($GLOBALS['TL_DCA'][$table]['config']['notDeletable'] ?? false) {
            return false;
        }

        return $this->canOperate($token, $table, 'delete');
    }

    private function canOperate(TokenInterface $token, string $table, string $permission): bool
    {
        if (
            \is_array($GLOBALS['TL_DCA'][$table]['config']['permissions'] ?? null)
            && !\in_array($permission, $GLOBALS['TL_DCA'][$table]['config']['permissions'], true)
        ) {
            return true;
        }

        return $this->accessDecisionManager->decide($token, [ContaoCorePermissions::USER_CAN_OPERATE_ON_TABLE], "$table::$permission");
    }
}

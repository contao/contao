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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

Abstract class AbstractDataContainerVoter implements VoterInterface, CacheableVoterInterface
{
    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === ContaoCorePermissions::DC_PREFIX.$this->getTable();
    }

    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, ReadAction::class, UpdateAction::class, DeleteAction::class], true);
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $isGranted = match (true) {
                $subject instanceof CreateAction,
                $subject instanceof ReadAction,
                $subject instanceof UpdateAction,
                $subject instanceof DeleteAction => $this->isGranted($subject),
                default => false,
            };

            if (!$isGranted) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    abstract protected function getTable(): string;

    abstract protected function isGranted(CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool;
}

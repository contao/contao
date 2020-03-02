<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Authorization\DcaPermission;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class AbstractDcaVoter extends Voter
{
    public function getFrontendUser(TokenInterface $token): ?FrontendUser
    {
        return $token->getUser() instanceof FrontendUser ? $token->getUser() : null;
    }

    public function getBackendUser(TokenInterface $token): ?BackendUser
    {
        return $token->getUser() instanceof BackendUser ? $token->getUser() : null;
    }

    abstract protected function getTable(): string;

    protected function supports($attribute, $subject): bool
    {
        if (!$subject instanceof DcaPermission) {
            return false;
        }

        return $this->getTable() === $subject->getTable() && \in_array($attribute, $this->getValidOperations(), true);
    }

    protected function isCollectionOperation(string $operation): bool
    {
        return \in_array($operation, $this->getCollectionOperations(), true);
    }

    protected function getCollectionOperations(): array
    {
        return [
            'paste',
            'select',
            'editAll',
            'deleteAll',
            'overrideAll',
            'cutAll',
            'copyAll',
            'showAll',
        ];
    }

    protected function getItemOperations(): array
    {
        return [
            'create',
            'edit',
            'copy',
            'delete',
            'show',
        ];
    }

    private function getValidOperations(): array
    {
        return array_unique(array_merge($this->getCollectionOperations(), $this->getItemOperations()));
    }
}

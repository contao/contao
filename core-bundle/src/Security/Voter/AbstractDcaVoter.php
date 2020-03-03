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
use Contao\CoreBundle\Security\Authorization\DcaSubject\ParentSubject;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class AbstractDcaVoter extends Voter
{
    public const OPERATION_CREATE = 'create';
    public const OPERATION_EDIT = 'edit';
    public const OPERATION_DELETE = 'delete';
    public const OPERATION_COPY = 'copy';
    public const OPERATION_CUT = 'cut';
    public const OPERATION_SHOW = 'show';
    public const OPERATION_PASTE = 'paste';

    public function getFrontendUser(TokenInterface $token): ?FrontendUser
    {
        return $token->getUser() instanceof FrontendUser ? $token->getUser() : null;
    }

    public function getBackendUser(TokenInterface $token): ?BackendUser
    {
        return $token->getUser() instanceof BackendUser ? $token->getUser() : null;
    }

    abstract protected function getTable(): string;

    abstract protected function getSubjectByAttributes(): array;

    protected function isSubjectOfDesiredType(string $attribute, $subject)
    {
        $supportedSubjects = $this->getSubjectByAttributes();

        if (!\array_key_exists($attribute, $supportedSubjects)) {
            return false;
        }

        if (!is_a($subject, $supportedSubjects[$attribute], true)) {
            return false;
        }

        return true;
    }

    protected function supports($attribute, $subject): bool
    {
        if (!$this->isSubjectOfDesiredType($attribute, $subject)) {
            return false;
        }

        if ($subject instanceof ParentSubject) {
            return $this->getTable() === $subject->getTable();
        }

        return false;
    }
}

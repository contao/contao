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
use Contao\CoreBundle\Security\Authorization\DcaSubject\RootSubject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

abstract class AbstractDcaVoter implements VoterInterface
{
    public const OPERATION_LIST = 'list';
    public const OPERATION_CREATE = 'create';
    public const OPERATION_EDIT = 'edit';
    public const OPERATION_DELETE = 'delete';
    public const OPERATION_COPY = 'copy';
    public const OPERATION_CUT = 'cut';
    public const OPERATION_SHOW = 'show';
    public const OPERATION_PASTE = 'paste';

    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        // abstain vote by default in case none of the attributes are supported
        $vote = self::ACCESS_ABSTAIN;

        // We only support one attribute
        if (1 !== \count($attributes)) {
            return $vote;
        }

        // Only DCA subjects are supported
        if (!$subject instanceof RootSubject) {
            return $vote;
        }

        // If it is not a back end user, it's not supported
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return $vote;
        }

        $attribute = $attributes[0];

        if (!$this->supports($attribute, $subject)) {
            return $vote;
        }

        // If user is admin, we allow access
        if ($user->isAdmin) {
            return self::ACCESS_GRANTED;
        }

        if (!$this->voteOnAttribute($attribute, $subject, $user, $token)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_GRANTED;
    }

    abstract protected function supportsTable(string $table): bool;

    abstract protected function voteOnAttribute(string $attribute, RootSubject $subject, BackendUser $user, TokenInterface $token): bool;

    abstract protected function getSubjectByAttributes(): array;

    protected function isSubjectOfDesiredType(string $attribute, RootSubject $subject)
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

    protected function supports($attribute, RootSubject $subject): bool
    {
        if (!$this->isSubjectOfDesiredType($attribute, $subject)) {
            return false;
        }

        return $this->supportsTable($subject->getTable());
    }
}

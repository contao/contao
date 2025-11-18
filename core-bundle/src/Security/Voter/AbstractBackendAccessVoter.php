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
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
abstract class AbstractBackendAccessVoter extends Voter
{
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, Vote|null $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        $permission = explode('.', $attribute, 3);

        if ('contao_user' !== $permission[0] || !isset($permission[1])) {
            return false;
        }

        if ($user->isAdmin) {
            return true;
        }

        $field = $permission[1];

        if (!$subject && isset($permission[2])) {
            $subject = $permission[2];
        }

        return $this->checkAccess($subject, $field, $user);
    }

    protected function checkAccess(mixed $subject, string $field, BackendUser $user): bool
    {
        if (null === $subject) {
            return $this->checkAccess(null, $field, $user);
        }

        if (!\is_scalar($subject) && !\is_array($subject)) {
            return false;
        }

        if (!\is_array($subject)) {
            $subject = [$subject];
        }

        return $this->checkAccess($subject, $field, $user);
    }

    abstract protected function hasAccess(array|null $subject, string $field, BackendUser $user): bool;
}

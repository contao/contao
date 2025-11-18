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

class BackwardsCompatibilityBackendAccessVoter extends AbstractBackendAccessVoter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'contao_user.formp');
    }

    /**
     * Checks the user permissions against a field in tl_user(_group).
     */
    protected function hasAccess(array|null $subject, string $field, BackendUser $user): bool
    {
        trigger_deprecation('contao/core-bundle', '5.7', 'Checking access on contao_user.formp is deprecated, vote on contao_user.cud instead.');

        if (null === $subject) {
            return \count(preg_grep('/^tl_form::/', $user->cud)) > 0;
        }

        $subject = array_map(static fn ($v) => 'tl_form::'.$v, $subject);

        return \is_array($user->cud) && array_intersect($subject, $user->cud);
    }
}

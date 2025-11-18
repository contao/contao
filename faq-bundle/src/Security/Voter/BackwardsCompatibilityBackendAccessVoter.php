<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Voter\AbstractBackendAccessVoter;

class BackwardsCompatibilityBackendAccessVoter extends AbstractBackendAccessVoter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, 'contao_user.faqp');
    }

    /**
     * Checks the user permissions against a field in tl_user(_group).
     */
    protected function hasAccess(array|null $subject, string $field, BackendUser $user): bool
    {
        trigger_deprecation('contao/faq-bundle', '5.7', 'Checking access on contao_user.faqp is deprecated, vote on contao_user.cud instead.');

        if (null === $subject) {
            return \count(preg_grep('/^tl_faq_category::/', $user->cud)) > 0;
        }

        $subject = array_map(static fn ($v) => 'tl_faq_category::'.$v, $subject);

        return \is_array($user->cud) && array_intersect($subject, $user->cud);
    }
}

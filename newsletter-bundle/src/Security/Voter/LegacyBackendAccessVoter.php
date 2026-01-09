<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Voter\AbstractBackendAccessVoter;

class LegacyBackendAccessVoter extends AbstractBackendAccessVoter
{
    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, 'contao_user.newsletterp');
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute);
    }

    /**
     * Checks the user permissions against a field in tl_user(_group).
     */
    protected function hasAccess(array|null $subject, string $field, BackendUser $user): bool
    {
        trigger_deprecation('contao/newsletter-bundle', '5.7', 'Checking access on "contao_user.newsletterp" is deprecated and will no longer work in Contao 6. Vote on "contao_user.cud" instead.');

        if (null === $subject) {
            return \count(preg_grep('/^tl_newsletter_channel::/', $user->cud)) > 0;
        }

        $subject = array_map(static fn ($v) => 'tl_newsletter_channel::'.$v, $subject);

        return \is_array($user->cud) && array_intersect($subject, $user->cud);
    }
}

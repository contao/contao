<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Security\Voter;

use Contao\BackendUser;
use Contao\CoreBundle\Security\Voter\AbstractBackendAccessVoter;

class LegacyBackendAccessVoter extends AbstractBackendAccessVoter
{
    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, 'contao_user.calendarp')
            || str_starts_with($attribute, 'contao_user.calendarfeedp');
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
        trigger_deprecation('contao/calendar-bundle', '5.7', 'Checking access on "contao_user.'.$field.'" is deprecated and will no longer work in Contao 6. Vote on "contao_user.cud" instead.');

        $table = match ($field) {
            'calendarp' => 'tl_calendar',
            'calendarfeedp' => 'tl_calendar_feed',
            default => throw new \InvalidArgumentException('Invalid field '.$field),
        };

        if (null === $subject) {
            return \count(preg_grep('/^'.$table.'::/', $user->cud)) > 0;
        }

        $subject = array_map(static fn ($v) => $table.'::'.$v, $subject);

        return \is_array($user->cud) && array_intersect($subject, $user->cud);
    }
}

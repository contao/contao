<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Security\Voter;

use Contao\CalendarBundle\Security\ContaoCalendarPermissions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class CalendarCommentsVoter extends AbstractCommentsVoter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
    ) {
    }

    protected function supportsSource(string $source): bool
    {
        return 'tl_calendar_events' === $source;
    }

    protected function hasAccess(TokenInterface $token, string $source, int $parent): bool
    {
        $calendarId = $this->connection->fetchOne(
            'SELECT pid FROM tl_calendar_events WHERE id=?',
            [$parent],
        );

        return false !== $calendarId && $this->accessDecisionManager->decide($token, [ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR], $calendarId);
    }
}

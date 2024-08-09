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

use Contao\CalendarBundle\Security\ContaoCalendarPermissions;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDynamicPtableVoter;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @internal
 */
class CalendarContentVoter extends AbstractDynamicPtableVoter
{
    private array $calendars = [];

    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager,
        private readonly Connection $connection,
    ) {
        parent::__construct($connection);
    }

    public function reset(): void
    {
        parent::reset();

        $this->calendars = [];
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }

    protected function hasAccessToRecord(TokenInterface $token, string $table, int $id): bool
    {
        if ('tl_calendar_events' !== $table) {
            return true;
        }

        if (!$this->accessDecisionManager->decide($token, [ContaoCalendarPermissions::USER_CAN_ACCESS_MODULE])) {
            return false;
        }

        $calendarId = $this->getCalendarId($id);

        return $calendarId
            && $this->accessDecisionManager->decide($token, [ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR], $calendarId);
    }

    private function getCalendarId(int $eventId): int|null
    {
        if (!isset($this->calendars[$eventId])) {
            $this->calendars[$eventId] = $this->connection->fetchOne('SELECT pid FROM tl_calendar_events WHERE id=?', [$eventId]);
        }

        return $this->calendars[$eventId] ?: null;
    }
}

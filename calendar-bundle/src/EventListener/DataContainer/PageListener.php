<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\EventListener\DataContainer;

use Contao\CalendarBundle\Controller\Page\CalendarFeedController;
use Contao\CalendarBundle\Security\ContaoCalendarPermissions;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PageListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[AsCallback('tl_page', target: 'fields.eventCalendars.options')]
    public function getAllowedCalendars(): array
    {
        $calendars = $this->connection->createQueryBuilder()
            ->select('id, title')
            ->from('tl_calendar')
            ->fetchAllKeyValue()
        ;

        foreach (array_keys($calendars) as $id) {
            if (!$this->authorizationChecker->isGranted(ContaoCalendarPermissions::USER_CAN_EDIT_CALENDAR, (int) $id)) {
                unset($calendars[$id]);
            }
        }

        return $calendars;
    }

    #[AsHook('getPageStatusIcon')]
    public function getStatusIcon(object $page, string $image): string
    {
        if (CalendarFeedController::TYPE !== $page->type) {
            return $image;
        }

        return str_replace('regular', 'feed', $image);
    }
}

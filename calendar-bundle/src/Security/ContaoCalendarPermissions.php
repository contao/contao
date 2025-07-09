<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Security;

final class ContaoCalendarPermissions
{
    public const USER_CAN_ACCESS_MODULE = 'contao_user.modules.calendar';

    public const USER_CAN_EDIT_CALENDAR = 'contao_user.calendars';

    public const USER_CAN_CREATE_CALENDARS = 'contao_user.calendarp.create';

    public const USER_CAN_DELETE_CALENDARS = 'contao_user.calendarp.delete';

    public const USER_CAN_EDIT_FEED = 'contao_user.calendarfeeds';

    /**
     * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
     *             use ContaoCorePermissions::USER_CAN_ACCESS_PAGE_TYPE instead
     */
    public const USER_CAN_CREATE_FEEDS = 'contao_user.calendarfeedp.create';

    /**
     * @deprecated Deprecated since Contao 5.6, to be removed in Contao 6;
     *             use ContaoCorePermissions::USER_CAN_DELETE_PAGE instead
     */
    public const USER_CAN_DELETE_FEEDS = 'contao_user.calendarfeedp.delete';
}

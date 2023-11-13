<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Security;

final class ContaoNewsPermissions
{
    public const USER_CAN_EDIT_ARCHIVE = 'contao_user.news';

    public const USER_CAN_CREATE_ARCHIVES = 'contao_user.newp.create';

    public const USER_CAN_DELETE_ARCHIVES = 'contao_user.newp.delete';
}

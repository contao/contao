<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Security;

final class ContaoNewsletterPermissions
{
    public const USER_CAN_ACCESS_MODULE = 'contao_user.modules.newsletter';

    public const USER_CAN_EDIT_CHANNEL = 'contao_user.newsletters';

    public const USER_CAN_CREATE_CHANNELS = 'contao_user.newsletterp.create';

    public const USER_CAN_DELETE_CHANNELS = 'contao_user.newsletterp.delete';
}

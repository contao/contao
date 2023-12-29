<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Security;

final class ContaoFaqPermissions
{
    public const USER_CAN_ACCESS_MODULE = 'contao_user.modules.faq';

    public const USER_CAN_EDIT_CATEGORY = 'contao_user.faqs';

    public const USER_CAN_CREATE_CATEGORIES = 'contao_user.faqp.create';

    public const USER_CAN_DELETE_CATEGORIES = 'contao_user.faqp.delete';
}

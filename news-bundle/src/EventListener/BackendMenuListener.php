<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Contao\StringUtil;
use Symfony\Component\Security\Core\Security;

/**
 * Adjusts the link to the news module, so that non-admin users who can only
 * access one single news archive are directly taken there instead of to the
 * news archives overview page.
 *
 * @internal
 */
class BackendMenuListener
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(MenuEvent $event): void
    {
        if ('mainMenu' !== $event->getTree()->getName()) {
            return;
        }

        // Return if the node is not present in the menu
        if ((!$content = $event->getTree()->getChild('content')) || (!$news = $content->getChild('news'))) {
            return;
        }

        // Return if the user can create archives
        if ($this->security->isGranted(ContaoNewsPermissions::USER_CAN_CREATE_ARCHIVES)) {
            return;
        }

        $archiveIds = StringUtil::deserialize($this->security->getUser()->news, true);

        // Return if there is more than one news archive
        if (\count($archiveIds) > 1) {
            return;
        }

        $news->setUri($news->getUri().'&table=tl_news&id='.$archiveIds[0]);
    }
}

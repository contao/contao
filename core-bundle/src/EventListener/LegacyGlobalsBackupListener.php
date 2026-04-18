<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Stores the legacy globals before a page is rendered.
 *
 * @internal
 */
#[AsEventListener(priority: -1024)]
class LegacyGlobalsBackupListener
{
    public const ATTRIBUTE = '_globals_backup';

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->attributes->set(self::ATTRIBUTE, [
            $GLOBALS['TL_HEAD'] ?? [],
            $GLOBALS['TL_BODY'] ?? [],
            $GLOBALS['TL_MOOTOOLS'] ?? [],
            $GLOBALS['TL_JQUERY'] ?? [],
            $GLOBALS['TL_USER_CSS'] ?? [],
            $GLOBALS['TL_FRAMEWORK_CSS'] ?? [],
            $GLOBALS['TL_JAVASCRIPT'] ?? [],
            $GLOBALS['TL_CSS'] ?? [],
        ]);
    }
}

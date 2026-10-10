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

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * The priority must be lower than the one of the firewall listener (defaults to 8).
 *
 * @internal
 */
#[AsEventListener(priority: 7)]
class UserStateListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly LocaleAwareInterface $localeSwitcher,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            $GLOBALS['TL_USERNAME'] = $user->getUserIdentifier();
        }

        if (!$user instanceof BackendUser) {
            return;
        }

        $config = $this->framework->getAdapter(Config::class);

        $config->set('showHelp', $user->showHelp);
        $config->set('useRTE', $user->useRTE);
        $config->set('useCE', $user->useCE);
        $config->set('doNotCollapse', $user->doNotCollapse);
        $config->set('thumbnails', $user->thumbnails);

        if ($user->language) {
            $request = $event->getRequest();
            $request->setLocale($user->language);

            $this->localeSwitcher->setLocale($user->language);
        }
    }
}

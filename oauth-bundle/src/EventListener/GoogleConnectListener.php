<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\EventListener;

use Contao\FrontendUser;
use Contao\OAuthBundle\Event\OAuthConnectEvent;
use League\OAuth2\Client\Provider\GoogleUser;

/**
 * Updates a new front end user's details with data from Google.
 */
class GoogleConnectListener
{
    public function __invoke(OAuthConnectEvent $event): void
    {
        $oauthUser = $event->getOauthUser();
        $user = $event->getUser();

        if (!$event->getIsNew() || !$oauthUser instanceof GoogleUser || !$user instanceof FrontendUser) {
            return;
        }

        $user->email = $oauthUser->getEmail();
        $user->firstname = $oauthUser->getFirstName();
        $user->lastname = $oauthUser->getLastName();
        $user->language = $oauthUser->getLocale();
        $user->save();
    }
}

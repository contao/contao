<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Event;

use Contao\User;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\ResourceOwnerAccessTokenInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired whenever a user connects via OAuth (either existing or new user).
 */
class OAuthConnectEvent extends Event
{
    public function __construct(private ResourceOwnerAccessTokenInterface $accessToken, private OAuth2ClientInterface $client, private ResourceOwnerInterface $oauthUser, private User $user, private bool $isNew)
    {
    }

    public function getAccessToken(): ResourceOwnerAccessTokenInterface
    {
        return $this->accessToken;
    }

    public function getClient(): OAuth2ClientInterface
    {
        return $this->client;
    }

    public function getOauthUser(): ResourceOwnerInterface
    {
        return $this->oauthUser;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getIsNew(): bool
    {
        return $this->isNew;
    }
}

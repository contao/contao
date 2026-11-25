<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\AuthorizationServer;

use League\OAuth2\Server\CryptKeyInterface;

/**
 * @internal
 */
final class SigningKey implements CryptKeyInterface
{
    public function __construct(#[\SensitiveParameter] private readonly string $key)
    {
    }

    public function getKeyPath(): string
    {
        return '';
    }

    public function getPassPhrase(): string|null
    {
        return null;
    }

    public function getKeyContents(): string
    {
        return $this->key;
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle;

use Contao\OAuthServerBundle\AuthorizationServer\SigningKey;

/**
 * @internal
 */
class KeyProvider
{
    public function __construct(#[\SensitiveParameter] private readonly string $secret)
    {
    }

    public function getSigningKey(): SigningKey
    {
        return new SigningKey(hash_hmac('sha256', 'contao_oauth_signing', $this->secret, true));
    }

    /**
     * Used by League to encrypt authorization codes and refresh tokens.
     */
    public function getEncryptionKey(): string
    {
        return hash_hmac('sha256', 'contao_oauth_server', $this->secret);
    }
}

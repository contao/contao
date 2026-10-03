<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Webhook;

final class SecretEncryption
{
    private readonly string $key;

    public function __construct(#[\SensitiveParameter] string $secret)
    {
        $this->key = hash('sha256', $secret, true);
    }

    public function encrypt(#[\SensitiveParameter] string $secret): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode($nonce.sodium_crypto_secretbox($secret, $nonce, $this->key));
    }

    public function decrypt(#[\SensitiveParameter] string $ciphertext): string
    {
        if (false === ($data = base64_decode($ciphertext, true)) || \strlen($data) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new \RuntimeException('The webhook secret cannot be decrypted with the configured key.');
        }

        $nonce = substr($data, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plaintext = sodium_crypto_secretbox_open(substr($data, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $this->key);

        if (false === $plaintext) {
            throw new \RuntimeException('The webhook secret cannot be decrypted with the configured key.');
        }

        return $plaintext;
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle;

use League\OAuth2\Server\CryptKey;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class KeyProvider
{
    public function __construct(
        private readonly string $keyDir,
        #[\SensitiveParameter] private readonly string $secret,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function getPrivateKey(): CryptKey
    {
        $this->ensureKeys();

        return new CryptKey($this->keyDir.'/private.key');
    }

    public function getPublicKeyContents(): string
    {
        $this->ensureKeys();

        return (string) file_get_contents($this->keyDir.'/public.key');
    }

    /**
     * Used by League to encrypt authorization codes and refresh tokens.
     */
    public function getEncryptionKey(): string
    {
        return hash_hmac('sha256', 'contao_oauth', $this->secret);
    }

    private function ensureKeys(): void
    {
        if ($this->filesystem->exists($this->keyDir.'/private.key')) {
            return;
        }

        $key = openssl_pkey_new(['private_key_bits' => 3072, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if (false === $key || !openssl_pkey_export($key, $private)) {
            throw new \RuntimeException('Could not generate the OAuth key pair.');
        }

        $public = openssl_pkey_get_details($key)['key'] ?? throw new \RuntimeException('Could not export the public key.');

        $this->filesystem->mkdir($this->keyDir, 0700);
        $this->filesystem->dumpFile($this->keyDir.'/private.key', $private);
        $this->filesystem->dumpFile($this->keyDir.'/public.key', $public);
        $this->filesystem->chmod($this->keyDir.'/private.key', 0600);
    }
}

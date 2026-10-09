<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Webhook;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Webhook\SecretEncryption;

class SecretEncryptionTest extends TestCase
{
    public function testEncryptsAndDecryptsWithIndependentNonces(): void
    {
        $encryption = new SecretEncryption('app-secret');

        $first = $encryption->encrypt('private-secret');
        $second = $encryption->encrypt('private-secret');

        $this->assertNotSame($first, $second);
        $this->assertSame('private-secret', $encryption->decrypt($first));
    }

    public function testRejectsTamperedCiphertext(): void
    {
        $encryption = new SecretEncryption('app-secret');
        $encrypted = $encryption->encrypt('private-secret');
        $ciphertext = base64_decode($encrypted, true);
        if (false === $ciphertext) {
            $this->fail('The encrypted value must be valid base64.');
        }
        $ciphertext[\strlen($ciphertext) - 1] = \chr(\ord($ciphertext[\strlen($ciphertext) - 1]) ^ 1);

        $this->expectException(\RuntimeException::class);

        $encryption->decrypt(base64_encode($ciphertext));
    }

    public function testDifferentAppSecretsCannotDecrypt(): void
    {
        $encrypted = new SecretEncryption('app-secret')->encrypt('private-secret');

        $this->expectException(\RuntimeException::class);

        new SecretEncryption('different-app-secret')->decrypt($encrypted);
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Encoder;

use Contao\CoreBundle\Security\Encoder\Sha1PasswordEncoder;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class Sha1PasswordEncoderTest extends TestCase
{
    /**
     * @var Sha1PasswordEncoder
     */
    private $encoder;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encoder = new Sha1PasswordEncoder();
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Security\Encoder\Sha1PasswordEncoder', $this->encoder);
    }

    public function testEncodesThePassword(): void
    {
        $raw = random_bytes(16);
        $salt = random_bytes(8);

        $this->assertSame(sha1($salt.$raw), $this->encoder->encodePassword($raw, $salt));
    }

    public function testFailsToEncodeThePasswordIfItIsTooLong(): void
    {
        $raw = random_bytes(BasePasswordEncoder::MAX_PASSWORD_LENGTH + 1);
        $salt = random_bytes(8);

        $this->expectException(BadCredentialsException::class);

        $this->encoder->encodePassword($raw, $salt);
    }

    public function testValidatesThePassword(): void
    {
        $raw = random_bytes(16);
        $long = random_bytes(BasePasswordEncoder::MAX_PASSWORD_LENGTH + 1);
        $salt = random_bytes(8);

        $this->assertTrue($this->encoder->isPasswordValid(sha1($salt.$raw), $raw, $salt));
        $this->assertFalse($this->encoder->isPasswordValid('', $raw, $salt));
        $this->assertFalse($this->encoder->isPasswordValid(sha1($salt.$raw), $long, $salt));
        $this->assertFalse($this->encoder->isPasswordValid('', $long, $salt));
    }
}

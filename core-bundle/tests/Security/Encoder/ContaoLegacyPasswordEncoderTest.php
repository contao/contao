<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\Encoder;

use Contao\CoreBundle\Security\Encoder\ContaoLegacyPasswordEncoder;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Security\Core\Encoder\BasePasswordEncoder;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ContaoLegacyPasswordEncoderTest extends TestCase
{
    /**
     * @var ContaoLegacyPasswordEncoder
     */
    private $encoder;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->encoder = new ContaoLegacyPasswordEncoder();
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Security\Encoder\ContaoLegacyPasswordEncoder', $this->encoder);
    }

    public function testEncodesThePassword(): void
    {
        $raw = random_bytes(16);
        $salt = random_bytes(8);

        $this->assertSame(sha1($salt.$raw), $this->encoder->encodePassword($raw, $salt));
    }

    public function testFailsIfThePasswordIsTooLong(): void
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

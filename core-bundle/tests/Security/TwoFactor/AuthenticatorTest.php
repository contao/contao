<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\TwoFactor;

use Contao\BackendUser;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Tests\TestCase;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

class AuthenticatorTest extends TestCase
{
    public function testValidatesTheCode(): void
    {
        $secret = random_bytes(128);
        $totp = TOTP::create(Base32::encodeUpperUnpadded($secret));

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->secret = $secret;

        $authenticator = new Authenticator();

        $this->assertTrue($authenticator->validateCode($user, $totp->now()));
        $this->assertFalse($authenticator->validateCode($user, 'foobar'));
    }

    public function testGeneratesTheProvisionUri(): void
    {
        $secret = random_bytes(128);

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->secret = $secret;

        $user
            ->expects($this->exactly(2))
            ->method('getUsername')
            ->willReturn('foobar')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->exactly(2))
            ->method('getSchemeAndHttpHost')
            ->willReturn('https://example.com')
        ;

        $authenticator = new Authenticator();

        $this->assertSame(
            sprintf(
                'otpauth://totp/https%%3A%%2F%%2Fexample.com:foobar@https%%3A%%2F%%2Fexample.com?secret=%s&issuer=https%%3A%%2F%%2Fexample.com',
                Base32::encodeUpperUnpadded($secret)
            ),
            $authenticator->getProvisionUri($user, $request)
        );

        $this->assertNotSame(
            sprintf(
                'otpauth://totp/https%%3A%%2F%%2Fexample.com:foobar@https%%3A%%2F%%2Fexample.com?secret=%s&issuer=https%%3A%%2F%%2Fexample.com',
                Base32::encodeUpperUnpadded('foobar')
            ),
            $authenticator->getProvisionUri($user, $request)
        );
    }

    public function testGeneratesTheQrCode(): void
    {
        $beginSvg = <<<'SVG'
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="180" height="180" viewBox="0 0 180 180"><rect x="0" y="0" width="180" height="180" fill="#fefefe"/>
SVG;

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->secret = 'foobar';

        $user
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn('foobar')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getSchemeAndHttpHost')
            ->willReturn('https://example.com')
        ;

        $authenticator = new Authenticator();
        $svg = $authenticator->getQrCode($user, $request);

        $this->assertSame(7192, \strlen($svg));
        $this->assertSame(0, strpos($svg, $beginSvg));
    }
}

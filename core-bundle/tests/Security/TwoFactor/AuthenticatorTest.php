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

use BaconQrCode\Common\Version;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Module\SquareModule;
use BaconQrCode\Renderer\Path\Close;
use BaconQrCode\Renderer\RendererStyle\EyeFill;
use BaconQrCode\Renderer\RendererStyle\Fill;
use Contao\BackendUser;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Tests\TestCase;
use DASPRiD\Enum\AbstractEnum;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Symfony\Component\HttpFoundation\Request;

class AuthenticatorTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([SquareModule::class, Fill::class, EyeFill::class, Encoder::class, AbstractEnum::class, Version::class, Close::class]);

        parent::tearDown();
    }

    public function testValidatesTheCode(): void
    {
        $secret = $this->generateSecret(1);
        $totp = TOTP::create(Base32::encodeUpperUnpadded($secret));

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->secret = $secret;

        $authenticator = new Authenticator();

        $this->assertTrue($authenticator->validateCode($user, $totp->now()));
        $this->assertFalse($authenticator->validateCode($user, 'foobar'));
    }

    public function testValidatesTheCodeOfPreviousWindow(): void
    {
        $secret = $this->generateSecret(2);
        $now = 1586161036;
        $fourtySecondsAgo = $now - 40;

        $totp = TOTP::create(Base32::encodeUpperUnpadded($secret));

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->secret = $secret;

        $authenticator = new Authenticator();

        $this->assertTrue($authenticator->validateCode($user, $totp->at($now), $now));
        $this->assertTrue($authenticator->validateCode($user, $totp->at($fourtySecondsAgo), $now));
        $this->assertFalse($authenticator->validateCode($user, 'foobar', $now));
    }

    public function testGeneratesTheProvisionUri(): void
    {
        $secret = $this->generateSecret(3);

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->secret = $secret;

        $user
            ->expects($this->exactly(2))
            ->method('getUserIdentifier')
            ->willReturn('foobar')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->exactly(2))
            ->method('getHttpHost')
            ->willReturn('example.com')
        ;

        $authenticator = new Authenticator();

        $this->assertSame(
            sprintf(
                'otpauth://totp/example.com:foobar@example.com?secret=%s&issuer=example.com',
                Base32::encodeUpperUnpadded($secret),
            ),
            $authenticator->getProvisionUri($user, $request),
        );

        $this->assertNotSame(
            sprintf(
                'otpauth://totp/example.com:foobar@example.com?secret=%s&issuer=example.com',
                Base32::encodeUpperUnpadded('foobar'),
            ),
            $authenticator->getProvisionUri($user, $request),
        );
    }

    public function testGeneratesTheQrCode(): void
    {
        $beginSvg = <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="180" height="180" viewBox="0 0 180 180"><rect x="0" y="0" width="180" height="180" fill="#fefefe"/>
            SVG;

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->secret = 'foobar';

        $user
            ->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('foobar')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getHttpHost')
            ->willReturn('example.com')
        ;

        $authenticator = new Authenticator();
        $svg = $authenticator->getQrCode($user, $request);

        $this->assertSame(5897, \strlen($svg));
        $this->assertSame(0, strpos($svg, $beginSvg));
    }

    /**
     * Generate pseudorandom secret from fixed seed to be deterministic.
     */
    private function generateSecret(int $seed): string
    {
        mt_srand($seed);

        $return = '';

        while (\strlen($return) < 128) {
            $return .= \chr(mt_rand() % 256);
        }

        return $return;
    }
}

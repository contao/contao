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

use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackendFormRenderer;
use Contao\CoreBundle\Security\TwoFactor\Provider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;

class ProviderTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $renderer = $this->createMock(BackendFormRenderer::class);
        $provider = new Provider($authenticator, $renderer, false);

        $this->assertInstanceOf('Contao\CoreBundle\Security\TwoFactor\Provider', $provider);
    }

    public function testReturnsTheFormRenderer(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $renderer = $this->createMock(BackendFormRenderer::class);
        $provider = new Provider($authenticator, $renderer, false);

        $this->assertInstanceOf(
            'Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface',
            $provider->getFormRenderer()
        );
    }

    public function testDoesNotBeginAuthenticationWithAnInvalidUser(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $renderer = $this->createMock(BackendFormRenderer::class);
        $context = $this->createMock(AuthenticationContextInterface::class);

        $context
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $provider = new Provider($authenticator, $renderer, false);

        $this->assertFalse($provider->beginAuthentication($context));
    }

    public function testGeneratesASecretIfThereIsNoneYet(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $renderer = $this->createMock(BackendFormRenderer::class);
        $user = $this->createMock(User::class);

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $context = $this->createMock(AuthenticationContextInterface::class);

        $context
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $provider = new Provider($authenticator, $renderer, true);

        $this->assertTrue($provider->beginAuthentication($context));
    }

    public function testDoesNotBeginAuthenticationIfTwoFactorIsDisabled(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $renderer = $this->createMock(BackendFormRenderer::class);

        $user = $this->createMock(User::class);
        $user->confirmedTwoFactor = false;

        $context = $this->createMock(AuthenticationContextInterface::class);

        $context
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $provider = new Provider($authenticator, $renderer, false);

        $this->assertFalse($provider->beginAuthentication($context));
    }

    public function testBeginsAuthenticationIfTwoFactorIsEnforced(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $renderer = $this->createMock(BackendFormRenderer::class);

        $user = $this->createMock(User::class);
        $user->confirmedTwoFactor = false;

        $context = $this->createMock(AuthenticationContextInterface::class);

        $context
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $provider = new Provider($authenticator, $renderer, true);

        $this->assertTrue($provider->beginAuthentication($context));
    }

    public function testBeginsAuthenticationIfTwoFactorIsEnabled(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $renderer = $this->createMock(BackendFormRenderer::class);

        $user = $this->createMock(User::class);
        $user->confirmedTwoFactor = true;

        $context = $this->createMock(AuthenticationContextInterface::class);

        $context
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $provider = new Provider($authenticator, $renderer, false);

        $this->assertTrue($provider->beginAuthentication($context));
    }

    public function testDoesNotValidateTheAuthenticationCodeIfTheUserIsInvalid(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $renderer = $this->createMock(BackendFormRenderer::class);
        $provider = new Provider($authenticator, $renderer, false);

        $this->assertFalse($provider->validateAuthenticationCode(null, ''));
    }

    public function testDoesNotValidateTheAuthenticationCodeIfTheCodeIsInvalid(): void
    {
        $user = $this->createMock(User::class);
        $renderer = $this->createMock(BackendFormRenderer::class);
        $authenticator = $this->createMock(Authenticator::class);

        $authenticator
            ->expects($this->once())
            ->method('validateCode')
            ->with($user, '123456')
            ->willReturn(false)
        ;

        $provider = new Provider($authenticator, $renderer, false);

        $this->assertFalse($provider->validateAuthenticationCode($user, '123456'));
    }

    public function testSetsTheTwoFactorFlagIfTheAuthenticationIsValid(): void
    {
        $user = $this->createMock(User::class);
        $user->confirmedTwoFactor = false;

        $user
            ->expects($this->once())
            ->method('save')
            ->willReturn(null)
        ;

        $renderer = $this->createMock(BackendFormRenderer::class);
        $authenticator = $this->createMock(Authenticator::class);

        $authenticator
            ->expects($this->once())
            ->method('validateCode')
            ->with($user, '123456')
            ->willReturn(true)
        ;

        $provider = new Provider($authenticator, $renderer, true);
        $provider->validateAuthenticationCode($user, '123456');

        $this->assertTrue($user->confirmedTwoFactor);
    }

    public function testValidatesTheAuthenticationCode(): void
    {
        $user = $this->createMock(User::class);
        $user->confirmedTwoFactor = true;

        $renderer = $this->createMock(BackendFormRenderer::class);
        $authenticator = $this->createMock(Authenticator::class);

        $authenticator
            ->expects($this->once())
            ->method('validateCode')
            ->with($user, '123456')
            ->willReturn(true)
        ;

        $provider = new Provider($authenticator, $renderer, false);

        $this->assertTrue($provider->validateAuthenticationCode($user, '123456'));
    }
}

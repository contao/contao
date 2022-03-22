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
use Contao\CoreBundle\Security\TwoFactor\Provider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\User;
use Scheb\TwoFactorBundle\Security\TwoFactor\AuthenticationContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ProviderTest extends TestCase
{
    public function testDoesNotBeginAuthenticationWithAnInvalidUser(): void
    {
        $authenticator = $this->createMock(Authenticator::class);

        $context = $this->createMock(AuthenticationContextInterface::class);
        $context
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $provider = new Provider($authenticator);

        $this->assertFalse($provider->beginAuthentication($context));
    }

    public function testDoesNotBeginAuthenticationIfTwoFactorIsDisabled(): void
    {
        $authenticator = $this->createMock(Authenticator::class);

        $user = $this->mockClassWithProperties(User::class);
        $user->useTwoFactor = false;

        $context = $this->createMock(AuthenticationContextInterface::class);
        $context
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $provider = new Provider($authenticator);

        $this->assertFalse($provider->beginAuthentication($context));
    }

    public function testBeginsAuthenticationIfTwoFactorIsEnabled(): void
    {
        $authenticator = $this->createMock(Authenticator::class);

        $user = $this->mockClassWithProperties(User::class);
        $user->useTwoFactor = true;

        $context = $this->createMock(AuthenticationContextInterface::class);
        $context
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $provider = new Provider($authenticator);

        $this->assertTrue($provider->beginAuthentication($context));
    }

    public function testDoesNotValidateTheAuthenticationCodeIfTheUserIsInvalid(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $provider = new Provider($authenticator);

        $this->assertFalse($provider->validateAuthenticationCode(new \stdClass(), ''));
    }

    public function testDoesNotValidateTheAuthenticationCodeIfTheCodeIsInvalid(): void
    {
        $user = $this->createMock(User::class);

        $authenticator = $this->createMock(Authenticator::class);
        $authenticator
            ->expects($this->once())
            ->method('validateCode')
            ->with($user, '123456')
            ->willReturn(false)
        ;

        $provider = new Provider($authenticator);

        $this->assertFalse($provider->validateAuthenticationCode($user, '123456'));
    }

    public function testValidatesTheAuthenticationCode(): void
    {
        $user = $this->mockClassWithProperties(User::class, ['useTwoFactor' => true]);

        $authenticator = $this->createMock(Authenticator::class);
        $authenticator
            ->expects($this->once())
            ->method('validateCode')
            ->with($user, '123456')
            ->willReturn(true)
        ;

        $provider = new Provider($authenticator);

        $this->assertTrue($provider->validateAuthenticationCode($user, '123456'));
    }

    public function testThrowsAnExceptionWhenTryingToGetTheFormRenderer(): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $provider = new Provider($authenticator);

        $this->expectException('RuntimeException');

        $provider->getFormRenderer();
    }
}

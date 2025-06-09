<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Voter;

use Contao\CoreBundle\Entity\WebauthnCredential;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\Voter\WebauthnCredentialVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\TrustPath\EmptyTrustPath;

class WebauthnCredentialVoterTest extends TestCase
{
    private WebauthnCredentialVoter $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voter = new WebauthnCredentialVoter();
    }

    public function testAbstainsIfTheAttributeIsNotWebauthnCredentialOwnership(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $credential = $this->createMock(WebauthnCredential::class);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, $credential, ['contao_foobar']));
    }

    public function testAbstainsIfTheSubjectIsNotWebauthnCredential(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, new \stdClass(), [ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP]));
    }

    public function testDeniesAccessIfIsNotAFrontendUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $credential = $this->createMock(WebauthnCredential::class);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $credential, [ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP]));
    }

    public function testDeniesAccessIfTokenIsTwoFactor(): void
    {
        $token = $this->createMock(TwoFactorTokenInterface::class);
        $credential = $this->createMock(WebauthnCredential::class);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $credential, [ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP]));
    }

    public function testDeniesAccessIfWebauthnCredentialDoesNotBelongToUser(): void
    {
        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('getPasskeyUserHandle')
            ->willReturn('frontend.1')
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $credential = new WebauthnCredential(
            'publicKeyCredentialId',
            'type',
            ['transport'],
            'attestationType',
            EmptyTrustPath::create(),
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            'credentialPublicKey',
            'frontend.42',
            1,
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $credential, [ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP]));
    }

    public function testGrantsAccessIfWebauthnCredentialBelongsToUser(): void
    {
        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('getPasskeyUserHandle')
            ->willReturn('frontend.42')
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $credential = new WebauthnCredential(
            'publicKeyCredentialId',
            'type',
            ['transport'],
            'attestationType',
            EmptyTrustPath::create(),
            Uuid::fromString('00000000-0000-0000-0000-000000000000'),
            'credentialPublicKey',
            'frontend.42',
            1,
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $credential, [ContaoCorePermissions::WEBAUTHN_CREDENTIAL_OWNERSHIP]));
    }
}

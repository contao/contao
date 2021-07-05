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

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\Voter\MemberGroupVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class MemberGroupVoterTest extends TestCase
{
    /**
     * @var MemberGroupVoter
     */
    private $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voter = new MemberGroupVoter();
    }

    public function testAbstainsIfTheAttributeIsNotContaoGroup(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, '1', ['contao_foobar']));
    }

    public function testDeniesAccessIfIsNotAFrontendUserAndGuestsAreNotAllowed(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, '1', [ContaoCorePermissions::MEMBER_IN_GROUPS]));
    }

    public function testGrantsAccessIfIsNotAFrontendUserAndGuestsAreAllowed(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, ['-1', '1'], [ContaoCorePermissions::MEMBER_IN_GROUPS]));
    }

    public function testDeniesAccessIfTheUserObjectDeniesAccess(): void
    {
        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('isMemberOf')
            ->willReturn(false)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, '1', [ContaoCorePermissions::MEMBER_IN_GROUPS]));
    }

    public function testGrantsAccessIfTheUserObjectGrantsAccess(): void
    {
        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('isMemberOf')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, '1', [ContaoCorePermissions::MEMBER_IN_GROUPS]));
    }

    public function testGrantsAccessForMultipleIds(): void
    {
        $ids = [1, 2, 3, 4];

        $user = $this->createMock(FrontendUser::class);
        $user
            ->expects($this->once())
            ->method('isMemberOf')
            ->with($ids)
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $ids, [ContaoCorePermissions::MEMBER_IN_GROUPS]));
    }
}

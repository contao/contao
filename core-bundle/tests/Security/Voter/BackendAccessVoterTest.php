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

use Contao\BackendUser;
use Contao\CoreBundle\Security\Voter\BackendAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class BackendAccessVoterTest extends TestCase
{
    /**
     * @var BackendAccessVoter
     */
    private $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->voter = new BackendAccessVoter();
    }

    public function testAbstainsIfTheAttributeIsNotAString(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $attributes = [new Expression('!is_granted("ROLE_MEMBER")')];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', $attributes));
    }

    public function testAbstainsIfThereIsNoContaoUserAttribute(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $attributes = ['foo', 'bar', 'contao.', 'contao_user_name'];

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($token, 'foo', $attributes));
    }

    public function testDeniesAccessIfTheTokenDoesNotHaveABackendUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null)
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, 'alexf', ['contao_user.']));
    }

    public function testDeniesAccessIfTheSubjectIsNotAScalarOrArray(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(BackendUser::class))
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, new \stdClass(), ['contao_user.']));
    }

    public function testDeniesAccessIfTheUserObjectDeniesAccess(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('hasAccess')
            ->willReturn(false)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, 'alexf', ['contao_user.']));
    }

    public function testGrantsAccessIfTheUserObjectGrantsAccess(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('hasAccess')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, 'alexf', ['contao_user.']));
    }
}

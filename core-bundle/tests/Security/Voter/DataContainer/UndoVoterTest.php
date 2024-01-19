<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Voter\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\UndoVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UndoVoterTest extends TestCase
{
    public function testSupportsAttributesAndTypes(): void
    {
        $voter = new UndoVoter($this->createMock(AccessDecisionManagerInterface::class));

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_undo'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
    }

    public function testAbstainsForAdmin(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->never())
            ->method('getUser')
        ;

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($token, ['ROLE_ADMIN'])
            ->willReturn(true)
        ;

        $voter = new UndoVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_undo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_undo'],
            ),
        );
    }

    public function testDeniesAccessIfUserIsNotABackendUser(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($token, ['ROLE_ADMIN'])
            ->willReturn(false)
        ;

        $voter = new UndoVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_undo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_undo'],
            ),
        );
    }

    /**
     * @dataProvider voteOnActionProvider
     */
    public function testVoteOnAction(array $user, CreateAction|DeleteAction|ReadAction|UpdateAction $action, int $expectedVote): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, $user);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($token, ['ROLE_ADMIN'])
            ->willReturn(false)
        ;

        $voter = new UndoVoter($accessDecisionManager);

        $this->assertSame(
            $expectedVote,
            $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_undo']),
        );
    }

    public function voteOnActionProvider(): \Generator
    {
        yield [
            ['id' => 42],
            new CreateAction('tl_undo', ['pid' => 42]),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            ['id' => 42],
            new ReadAction('tl_undo', ['pid' => 42]),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            ['id' => 42],
            new UpdateAction('tl_undo', ['pid' => 42]),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            ['id' => 42],
            new DeleteAction('tl_undo', ['pid' => 42]),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            ['id' => 42],
            new ReadAction('tl_undo', ['pid' => 21]),
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['id' => 42],
            new UpdateAction('tl_undo', ['pid' => 42], ['pid' => 21]),
            VoterInterface::ACCESS_DENIED,
        ];
    }
}

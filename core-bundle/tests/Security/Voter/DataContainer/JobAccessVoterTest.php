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
use Contao\CoreBundle\Security\Voter\DataContainer\JobAccessVoter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class JobAccessVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $voter = new JobAccessVoter($this->createStub(AccessDecisionManagerInterface::class));

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_job'));
        $this->assertFalse($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertFalse($voter->supportsType(UpdateAction::class));
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

        $voter = new JobAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_job', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_job'],
            ),
        );
    }

    public function testDeniesAccessIfUserIsNotABackendUser(): void
    {
        $user = $this->createClassWithPropertiesStub(FrontendUser::class);

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

        $voter = new JobAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_job', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_job'],
            ),
        );
    }

    #[DataProvider('voteOnActionProvider')]
    public function testVoteOnAction(array $user, DeleteAction|ReadAction $action, int $expectedVote): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, $user);

        $token = $this->createStub(TokenInterface::class);
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

        $voter = new JobAccessVoter($accessDecisionManager);

        $this->assertSame(
            $expectedVote,
            $voter->vote($token, $action, [ContaoCorePermissions::DC_PREFIX.'tl_job']),
        );
    }

    public static function voteOnActionProvider(): iterable
    {
        yield [
            ['id' => 42],
            new DeleteAction('tl_job', ['owner' => 42]),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            ['id' => 42],
            new DeleteAction('tl_job', ['owner' => 21]),
            VoterInterface::ACCESS_DENIED,
        ];

        yield [
            ['id' => 42],
            new ReadAction('tl_job', ['owner' => 0, 'public' => true]),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            ['id' => 42],
            new ReadAction('tl_job', ['owner' => 42, 'public' => false]),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield [
            ['id' => 42],
            new ReadAction('tl_job', ['owner' => 21, 'public' => false]),
            VoterInterface::ACCESS_DENIED,
        ];
    }
}

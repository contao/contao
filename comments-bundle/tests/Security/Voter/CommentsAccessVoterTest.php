<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Security\Voter;

use Contao\CommentsBundle\Security\ContaoCommentsPermissions;
use Contao\CommentsBundle\Security\Voter\CommentsAccessVoter;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CommentsAccessVoterTest extends TestCase
{
    public function testSupportsAttributesAndTypes(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->never())
            ->method('decide')
        ;

        $voter = new CommentsAccessVoter($accessDecisionManager);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_comments'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_foobar'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertFalse($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(CommentsAccessVoter::class));

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new UpdateAction('tl_comments', ['id' => 42]),
                ['whatever'],
            ),
        );
    }

    #[DataProvider('votesProvider')]
    public function testVotes(array $current, bool $accessGranted): void
    {
        $token = $this->createMock(TokenInterface::class);
        $subject = new UpdateAction('tl_comments', $current);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($token, [ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT], $subject->getCurrent())
            ->willReturn($accessGranted)
        ;

        $voter = new CommentsAccessVoter($accessDecisionManager);

        $this->assertSame(
            $accessGranted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                $subject,
                [ContaoCorePermissions::DC_PREFIX.'tl_comments'],
            ),
        );
    }

    public static function votesProvider(): iterable
    {
        yield [
            ['source' => 'tl_foo', 'parent' => 42],
            true,
        ];

        yield [
            ['source' => 'tl_foo', 'parent' => 42],
            false,
        ];
    }
}

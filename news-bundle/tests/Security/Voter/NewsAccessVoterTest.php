<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Tests\Security\Voter\DataContainer\AbstractAccessVoterTestCase;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Contao\NewsBundle\Security\Voter\NewsAccessVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class NewsAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['pid' => 42],
            [
                [[ContaoNewsPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['pid' => 42],
            [
                [[ContaoNewsPermissions::USER_CAN_ACCESS_MODULE], null, false],
            ],
            false,
        ];

        // Permission denied on news archive
        yield [
            ['pid' => 42],
            [
                [[ContaoNewsPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], 42, false],
            ],
            false,
        ];
    }

    public function testDeniesUpdateActionToNewParent(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(3))
            ->method('decide')
            ->willReturnMap([
                [$token, [ContaoNewsPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [$token, [ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], 42, true],
                [$token, [ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], 43, false],
            ])
        ;

        $voter = new NewsAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new UpdateAction('tl_news', ['pid' => 42], ['pid' => 43]),
                [ContaoCorePermissions::DC_PREFIX.'tl_news'],
            ),
        );
    }

    protected function getVoterClass(): string
    {
        return NewsAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_news';
    }
}

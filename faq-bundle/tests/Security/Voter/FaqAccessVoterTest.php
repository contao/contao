<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Tests\Security\Voter\DataContainer\AbstractAccessVoterTestCase;
use Contao\FaqBundle\Security\ContaoFaqPermissions;
use Contao\FaqBundle\Security\Voter\FaqAccessVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FaqAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['pid' => 42],
            [
                [[ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['pid' => 42],
            [
                [[ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], null, false],
            ],
            false,
        ];

        // Permission denied on faq category
        yield [
            ['pid' => 42],
            [
                [[ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], 42, false],
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
                [$token, [ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [$token, [ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], 42, true],
                [$token, [ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], 43, false],
            ])
        ;

        $voter = new FaqAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new UpdateAction('tl_faq', ['pid' => 42], ['pid' => 43]),
                [ContaoCorePermissions::DC_PREFIX.'tl_faq'],
            ),
        );
    }

    protected function getVoterClass(): string
    {
        return FaqAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_faq';
    }
}

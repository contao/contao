<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\Security\Voter;

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Tests\Security\Voter\DataContainer\AbstractAccessVoterTestCase;
use Contao\NewsletterBundle\Security\ContaoNewsletterPermissions;
use Contao\NewsletterBundle\Security\Voter\NewsletterAccessVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class NewsletterAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): iterable
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['pid' => 42],
            [
                [[ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['pid' => 42],
            [
                [[ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE], null, false],
            ],
            false,
        ];

        // Permission denied on newsletter channel
        yield [
            ['pid' => 42],
            [
                [[ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL], 42, false],
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
                [$token, [ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [$token, [ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL], 42, true],
                [$token, [ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL], 43, false],
            ])
        ;

        $voter = new NewsletterAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new UpdateAction('tl_newsletter', ['pid' => 42], ['pid' => 43]),
                [ContaoCorePermissions::DC_PREFIX.'tl_newsletter'],
            ),
        );
    }

    protected function getVoterClass(): string
    {
        return NewsletterAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_newsletter';
    }
}

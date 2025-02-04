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
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\FaqBundle\Security\ContaoFaqPermissions;
use Contao\FaqBundle\Security\Voter\FaqAccessVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FaqAccessVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $matcher = $this->exactly(5);
        $accessDecisionManager
            ->expects($matcher)
            ->method('decide')
                ->willReturnCallback(
                    function (...$parameters) use ($matcher, $token) {
                        if (1 === $matcher->numberOfInvocations()) {
                            $this->assertSame($token, $parameters[0]);
                            $this->assertSame([ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], $parameters[1]);

                            return true;
                        }
                        if (2 === $matcher->numberOfInvocations()) {
                            $this->assertSame($token, $parameters[0]);
                            $this->assertSame([ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], $parameters[1]);
                            $this->assertSame('42', $parameters[2]);

                            return true;
                        }
                        if (3 === $matcher->numberOfInvocations()) {
                            $this->assertSame($token, $parameters[0]);
                            $this->assertSame([ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], $parameters[1]);

                            return false;
                        }
                        if (4 === $matcher->numberOfInvocations()) {
                            $this->assertSame($token, $parameters[0]);
                            $this->assertSame([ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], $parameters[1]);

                            return true;
                        }
                        if (5 === $matcher->numberOfInvocations()) {
                            $this->assertSame($token, $parameters[0]);
                            $this->assertSame([ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], $parameters[1]);
                            $this->assertSame('42', $parameters[2]);

                            return false;
                        }
                    }
                )
            ;

        $voter = new FaqAccessVoter($accessDecisionManager);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_faq'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_faq_category'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(FaqAccessVoter::class));

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_faq', ['pid' => 42]),
                ['whatever'],
            ),
        );

        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('tl_faq', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_faq'],
            ),
        );

        // Permission denied on back end module
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_faq', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_faq'],
            ),
        );

        // Permission denied on faq category
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('tl_faq', ['pid' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_faq'],
            ),
        );
    }

    public function testDeniesUpdateActionToNewParent(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $matcher = $this->exactly(3);
        $accessDecisionManager
            ->expects($matcher)
            ->method('decide')
                ->willReturnCallback(
                    function (...$parameters) use ($matcher, $token) {
                        if (1 === $matcher->numberOfInvocations()) {
                            $this->assertSame($token, $parameters[0]);
                            $this->assertSame([ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], $parameters[1]);

                            return true;
                        }
                        if (2 === $matcher->numberOfInvocations()) {
                            $this->assertSame($token, $parameters[0]);
                            $this->assertSame([ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], $parameters[1]);
                            $this->assertSame('42', $parameters[2]);

                            return true;
                        }
                        if (3 === $matcher->numberOfInvocations()) {
                            $this->assertSame($token, $parameters[0]);
                            $this->assertSame([ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], $parameters[1]);
                            $this->assertSame(43, $parameters[2]);

                            return false;
                        }
                    }
                )
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
}

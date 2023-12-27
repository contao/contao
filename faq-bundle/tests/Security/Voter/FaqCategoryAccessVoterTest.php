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
use Contao\FaqBundle\Security\Voter\FaqCategoryAccessVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FaqCategoryAccessVoterTest extends TestCase
{
    public function testVoter(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(5))
            ->method('decide')
            ->withConsecutive(
                [$token, [ContaoFaqPermissions::USER_CAN_ACCESS_MODULE]],
                [$token, [ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], 42],
                [$token, [ContaoFaqPermissions::USER_CAN_ACCESS_MODULE]],
                [$token, [ContaoFaqPermissions::USER_CAN_ACCESS_MODULE]],
                [$token, [ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], 42],
            )
            ->willReturnOnConsecutiveCalls(true, true, false, true, false)
        ;

        $voter = new FaqCategoryAccessVoter($accessDecisionManager);

        $this->assertTrue($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_faq_category'));
        $this->assertFalse($voter->supportsAttribute(ContaoCorePermissions::DC_PREFIX.'tl_faq'));
        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(ReadAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertTrue($voter->supportsType(DeleteAction::class));
        $this->assertFalse($voter->supportsType(FaqCategoryAccessVoter::class));

        // Unsupported attribute
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                ['whatever'],
            ),
        );

        // Permission granted, so abstain! Our voters either deny or abstain,
        // they must never grant access (see #6201).
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_faq_category'],
            ),
        );

        // Permission denied on back end module
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_faq_category'],
            ),
        );

        // Permission denied on faq category
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new ReadAction('foo', ['id' => 42]),
                [ContaoCorePermissions::DC_PREFIX.'tl_faq_category'],
            ),
        );
    }
}

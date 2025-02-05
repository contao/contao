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

use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\FormFieldAccessVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FormFieldAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['pid' => 42],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['pid' => 42],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', false],
            ],
            false,
        ];

        // Permission denied on form
        yield [
            ['pid' => 42],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, false],
            ],
            false,
        ];
    }

    public function testDeniesUpdateActionToNewParent(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(3))
            ->method('decide')
            ->willReturnMap([
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [$token, [ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, true],
                [$token, [ContaoCorePermissions::USER_CAN_EDIT_FORM], 43, false],
            ])
        ;

        $voter = new FormFieldAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new UpdateAction('tl_form_field', ['pid' => 42], ['pid' => 43]),
                [ContaoCorePermissions::DC_PREFIX.'tl_form_field'],
            ),
        );
    }

    protected function getVoterClass(): string
    {
        return FormFieldAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_form_field';
    }
}

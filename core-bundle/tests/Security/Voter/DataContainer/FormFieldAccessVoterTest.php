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
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\FormFieldAccessVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class FormFieldAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        yield 'Permission granted with ReadAction' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, true],
            ],
            true,
            ReadAction::class,
        ];

        yield 'Permission granted with CreateAction' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'text', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, true],
            ],
            true,
            CreateAction::class,
        ];

        yield 'Permission granted with DeleteAction' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'text', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, true],
            ],
            true,
            DeleteAction::class,
        ];

        yield 'Permission denied on back end module' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', false],
            ],
            false,
        ];

        yield 'Permission denied on field type with CreateAction' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'text', false],
            ],
            false,
            CreateAction::class,
        ];

        yield 'Permission denied on field type with UpdateAction' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'text', false],
            ],
            false,
            UpdateAction::class,
        ];

        yield 'Permission denied on field type with DeleteAction' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'text', false],
            ],
            false,
            DeleteAction::class,
        ];

        yield 'Permission denied on form with ReadAction' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, false],
            ],
            false,
        ];

        yield 'Permission denied on form with DeleteAction' => [
            ['pid' => 42, 'type' => 'text'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'text', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, false],
            ],
            false,
            DeleteAction::class,
        ];
    }

    public function testDeniesUpdateActionToNewType(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(3))
            ->method('decide')
            ->willReturnMap([
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'foobar', true],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'text', false],
            ])
        ;

        $voter = new FormFieldAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new UpdateAction('tl_form_field', ['pid' => 42, 'type' => 'text'], ['pid' => 43, 'type' => 'foobar']),
                [ContaoCorePermissions::DC_PREFIX.'tl_form_field'],
            ),
        );
    }

    public function testDeniesUpdateActionToNewParent(): void
    {
        $token = $this->createStub(TokenInterface::class);

        $accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $accessDecisionManager
            ->expects($this->exactly(4))
            ->method('decide')
            ->willReturnMap([
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [$token, [ContaoCorePermissions::USER_CAN_ACCESS_FIELD_TYPE], 'text', true],
                [$token, [ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, true],
                [$token, [ContaoCorePermissions::USER_CAN_EDIT_FORM], 43, false],
            ])
        ;

        $voter = new FormFieldAccessVoter($accessDecisionManager);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $token,
                new UpdateAction('tl_form_field', ['pid' => 42, 'type' => 'text'], ['pid' => 43]),
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

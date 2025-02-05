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
use Contao\CoreBundle\Security\Voter\DataContainer\FormAccessVoter;

class FormAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['id' => 42],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['id' => 42],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', false],
            ],
            false,
        ];

        // Permission denied on form
        yield [
            ['id' => 42],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'form', true],
                [[ContaoCorePermissions::USER_CAN_EDIT_FORM], 42, false],
            ],
            false,
        ];
    }

    protected function getVoterClass(): string
    {
        return FormAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_form';
    }
}

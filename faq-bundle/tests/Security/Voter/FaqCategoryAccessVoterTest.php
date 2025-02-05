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

use Contao\CoreBundle\Tests\Security\Voter\DataContainer\AbstractAccessVoterTestCase;
use Contao\FaqBundle\Security\ContaoFaqPermissions;
use Contao\FaqBundle\Security\Voter\FaqCategoryAccessVoter;

class FaqCategoryAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['id' => 42],
            [
                [[ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['id' => 42],
            [
                [[ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], null, false],
            ],
            false,
        ];

        // Permission denied on faq category
        yield [
            ['id' => 42],
            [
                [[ContaoFaqPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoFaqPermissions::USER_CAN_EDIT_CATEGORY], 42, false],
            ],
            false,
        ];
    }

    protected function getVoterClass(): string
    {
        return FaqCategoryAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_faq_category';
    }
}

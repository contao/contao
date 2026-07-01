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

use Contao\CoreBundle\Tests\Security\Voter\DataContainer\AbstractAccessVoterTestCase;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Contao\NewsBundle\Security\Voter\NewsArchiveAccessVoter;

class NewsArchiveAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): iterable
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['id' => 42],
            [
                [[ContaoNewsPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['id' => 42],
            [
                [[ContaoNewsPermissions::USER_CAN_ACCESS_MODULE], null, false],
            ],
            false,
        ];

        // Permission denied on news archive
        yield [
            ['id' => 42],
            [
                [[ContaoNewsPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE], 42, false],
            ],
            false,
        ];
    }

    protected function getVoterClass(): string
    {
        return NewsArchiveAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_news_archive';
    }
}

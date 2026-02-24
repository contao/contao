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

use Contao\CoreBundle\Tests\Security\Voter\DataContainer\AbstractAccessVoterTestCase;
use Contao\NewsletterBundle\Security\ContaoNewsletterPermissions;
use Contao\NewsletterBundle\Security\Voter\NewsletterChannelAccessVoter;

class NewsletterChannelAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): iterable
    {
        // Permission granted, so abstain! Our voters either deny or abstain, they must
        // never grant access (see #6201).
        yield [
            ['id' => 42],
            [
                [[ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL], 42, true],
            ],
            true,
        ];

        // Permission denied on back end module
        yield [
            ['id' => 42],
            [
                [[ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE], null, false],
            ],
            false,
        ];

        // Permission denied on newsletter channel
        yield [
            ['id' => 42],
            [
                [[ContaoNewsletterPermissions::USER_CAN_ACCESS_MODULE], null, true],
                [[ContaoNewsletterPermissions::USER_CAN_EDIT_CHANNEL], 42, false],
            ],
            false,
        ];
    }

    protected function getVoterClass(): string
    {
        return NewsletterChannelAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_newsletter_channel';
    }
}

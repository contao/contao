<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Security\Voter;

use Contao\CommentsBundle\Security\ContaoCommentsPermissions;
use Contao\CommentsBundle\Security\Voter\CommentsAccessVoter;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Tests\Security\Voter\DataContainer\AbstractAccessVoterTestCase;

class CommentsAccessVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        yield [
            ['source' => 'tl_foo', 'parent' => 42],
            [],
            true,
            ReadAction::class,
        ];

        yield [
            ['source' => 'tl_foo', 'parent' => 42],
            [
                [[ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT], ['source' => 'tl_foo', 'parent' => 42], true],
            ],
            true,
            UpdateAction::class,
        ];

        yield [
            ['source' => 'tl_foo', 'parent' => 42],
            [
                [[ContaoCommentsPermissions::USER_CAN_ACCESS_COMMENT], ['source' => 'tl_foo', 'parent' => 42], false],
            ],
            false,
            UpdateAction::class,
        ];
    }

    protected function getActionClass(): string
    {
        return UpdateAction::class;
    }

    protected function getVoterClass(): string
    {
        return CommentsAccessVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_comments';
    }
}

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
use Contao\CoreBundle\Security\Voter\DataContainer\ThemeContentElementVoter;

class ThemeContentElementVoterTest extends AbstractAccessVoterTestCase
{
    public static function votesProvider(): \Generator
    {
        yield [
            ['ptable' => 'tl_theme'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'themes', true],
                [[ContaoCorePermissions::USER_CAN_ACCESS_THEME_CONTENT_ELEMENTS], null, true],
            ],
            true,
        ];

        yield [
            ['ptable' => 'tl_theme'],
            [
                [[ContaoCorePermissions::USER_CAN_ACCESS_MODULE], 'themes', true],
                [[ContaoCorePermissions::USER_CAN_ACCESS_THEME_CONTENT_ELEMENTS], null, false],
            ],
            false,
        ];
    }

    protected function getVoterClass(): string
    {
        return ThemeContentElementVoter::class;
    }

    protected function getTable(): string
    {
        return 'tl_content';
    }
}

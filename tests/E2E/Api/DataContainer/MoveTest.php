<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api\DataContainer;

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Api\ApiTestTrait;

class MoveTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    /**
     * Regression test for move requests being denormalized against the DataContainer
     * record schema instead of the move request schema.
     *
     * In particular, "target" must be accepted as a page ID here and must not be
     * interpreted as the boolean "target" field of tl_page.
     *
     * @throws \JsonException
     */
    public function testMovesAPageIntoAnotherParent(): void
    {
        $fixtures = $this->apiFixtures();
        $page = (int) $fixtures->value('page_api_source');
        $parent = (int) $fixtures->value('page_api_target');

        [$status, $response] = $this->apiRequest(
            'POST',
            '/dc/page/'.$page.'/move',
            [
                'target' => $parent,
                'position' => 'last',
            ],
        );

        $this->assertSame(200, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertSame($parent, $response['pid']['id']);
    }
}

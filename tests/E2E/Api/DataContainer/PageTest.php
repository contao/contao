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

class PageTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    public function testCreatesASubpage(): void
    {
        $parentId = (int) $this->apiFixtures()->value('page_api_target');

        [$status, $response] = $this->apiRequest(
            'POST',
            '/dc/page',
            [
                'pid' => ['iri' => '/contao/api/dc/page/'.$parentId],
                'title' => 'API subpage',
                'alias' => 'api-subpage',
                'type' => 'regular',
            ],
        );

        $this->assertSame(201, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertSame($parentId, $response['pid']['id']);
    }
}

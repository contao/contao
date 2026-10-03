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

class RelationTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    /**
     * @throws \JsonException
     */
    public function testCreatesANewsArchiveWithARedirectPage(): void
    {
        $pageId = (int) $this->apiFixtures()->value('page_api_target');

        [$status, $response] = $this->apiRequest(
            'POST',
            '/dc/news_archive',
            [
                'title' => 'API news',
                'jumpTo' => ['iri' => '/contao/api/dc/page/'.$pageId],
            ],
        );

        $this->assertSame(201, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertSame($pageId, $response['jumpTo']['id']);
    }
}

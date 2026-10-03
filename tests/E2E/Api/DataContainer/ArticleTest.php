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

class ArticleTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    /**
     * @throws \JsonException
     */
    public function testCreatesArticleOnPage(): void
    {
        $fixtures = $this->apiFixtures();
        $pageId = (int) $fixtures->value('page_api_target');

        [$status, $response] = $this->apiRequest(
            'POST',
            '/dc/article',
            [
                'pid' => ['iri' => '/contao/api/dc/page/'.$pageId],
                'title' => 'API article',
                'alias' => 'api-article',
            ],
        );

        $this->assertSame(201, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $response);
        $this->assertSame($pageId, $response['pid']['id']);

        [$status, $collection] = $this->apiRequest('GET', '/dc/article');

        $this->assertSame(200, $status, json_encode($collection, JSON_PRETTY_PRINT));
        $this->assertContains($response['@id'], array_column($collection['member'] ?? [], '@id'));
    }
}

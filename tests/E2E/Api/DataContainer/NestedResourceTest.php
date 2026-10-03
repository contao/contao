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

class NestedResourceTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    /**
     * @throws \JsonException
     */
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

    /**
     * @throws \JsonException
     */
    public function testRejectsAParentInThePayloadOfANestedRoute(): void
    {
        $fixtures = $this->apiFixtures();
        $articleId = (int) $fixtures->value('article_main_home');

        [$status, $response] = $this->apiRequest(
            'POST',
            '/dc/article/'.$articleId.'/content',
            [
                'pid' => ['iri' => '/contao/api/dc/article/'.$articleId],
                'type' => 'text',
            ],
        );

        $this->assertSame(422, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertStringContainsString('given by the route', $response['detail'] ?? '');
    }
}

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

    public function testIdentifiesRecordsInsideElementGroups(): void
    {
        $articleId = (int) $this->apiFixtures()->value('article_main_home');
        $path = '/dc/article/'.$articleId.'/content';

        [, $group] = $this->apiRequest('POST', $path, ['type' => 'element_group']);
        [, $innerGroup] = $this->apiRequest('POST', $path.'/'.$group['id']['id'].'/content', ['type' => 'element_group']);

        $this->assertArrayHasKey('@id', $innerGroup, json_encode($innerGroup, JSON_PRETTY_PRINT));

        [$status, $response] = $this->apiRequest(
            'POST',
            $path.'/'.$group['id']['id'].'/content/'.$innerGroup['id']['id'].'/content',
            [
                'type' => 'headline',
                'headline' => ['unit' => 'h2', 'value' => 'Nested'],
            ],
        );

        $this->assertSame(201, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $response);
        $this->assertSame($innerGroup['@id'], $response['pid']['@id'] ?? null);

        [$status, $read] = $this->apiRequest('GET', $response['@id']);

        $this->assertSame(200, $status, json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame('Nested', $read['headline']['value']);
    }
}

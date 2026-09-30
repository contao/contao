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

    public function testCreatesAndListsNestedRecords(): void
    {
        $content = '/contao/api/dc/article/'.$this->fixtureId('article').'/content';

        [$status, $group] = $this->apiRequest('POST', $content, ['type' => 'element_group']);

        $this->assertSame(201, $status, json_encode($group, JSON_PRETTY_PRINT));

        // Create a text element directly inside the element group via the recursive route
        $children = $content.'/'.basename($group['@id']).'/content';

        [$status, $text] = $this->apiRequest('POST', $children, ['type' => 'text', 'text' => '<p>Nested via API</p>']);

        $this->assertSame(201, $status, json_encode($text, JSON_PRETTY_PRINT));

        // The article only lists its direct children, the group lists the text element
        [$status, $articleElements] = $this->apiRequest('GET', $content);

        $this->assertSame(200, $status);
        $this->assertSame(['element_group'], array_column($this->members($articleElements), 'type'));

        [$status, $groupElements] = $this->apiRequest('GET', $children);

        $this->assertSame(200, $status);
        $this->assertSame(['text'], array_column($this->members($groupElements), 'type'));
    }

    public function testScopesRecordsToTheirParent(): void
    {
        $support = '/contao/api/dc/faq_category/'.$this->fixtureId('faq_category_support').'/faq';
        $products = '/contao/api/dc/faq_category/'.$this->fixtureId('faq_category_products').'/faq';

        // The author is mandatory as well, but defaults to the current user
        [$status, $faq] = $this->apiRequest('POST', $support, [
            'question' => 'Created via API?',
            'answer' => '<p>Yes</p>',
        ]);

        $this->assertSame(201, $status, json_encode($faq, JSON_PRETTY_PRINT));

        $id = basename($faq['@id']);

        [, $collection] = $this->apiRequest('GET', $support);

        $this->assertSame(['Created via API?'], array_column($this->members($collection), 'question'));

        [, $collection] = $this->apiRequest('GET', $products);

        $this->assertSame([], $this->members($collection));

        [$status] = $this->apiRequest('GET', $support.'/'.$id);

        $this->assertSame(200, $status);

        // The record exists, but not below the other category
        [$status] = $this->apiRequest('GET', $products.'/'.$id);

        $this->assertSame(404, $status);

        [$status] = $this->apiRequest('PATCH', $products.'/'.$id, ['question' => 'Wrong parent']);

        $this->assertSame(404, $status);

        [$status] = $this->apiRequest('DELETE', $products.'/'.$id);

        $this->assertSame(404, $status);

        [, $faq] = $this->apiRequest('GET', $support.'/'.$id);

        $this->assertSame('Created via API?', $faq['question']);
    }

    public function testReturnsNotFoundForAnUnknownParent(): void
    {
        [$status, $collection] = $this->apiRequest('GET', '/contao/api/dc/theme/'.$this->fixtureId('theme_editorial').'/image_size');

        $this->assertSame(200, $status);
        $this->assertEqualsCanonicalizing(['Content image', 'Teaser image'], array_column($this->members($collection), 'name'));

        [$status] = $this->apiRequest('GET', '/contao/api/dc/theme/999999/image_size');

        $this->assertSame(404, $status);
    }
}

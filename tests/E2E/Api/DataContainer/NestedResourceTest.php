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
}

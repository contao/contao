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

class DataContainerMoveTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    public function testMovesAContentElementIntoAnElementGroup(): void
    {
        $articleId = $this->fixtureId('article');
        $content = '/contao/api/dc/article/'.$articleId.'/content';

        [$status, $group] = $this->apiRequest('POST', $content, ['type' => 'element_group']);

        $this->assertSame(201, $status, json_encode($group, JSON_PRETTY_PRINT));

        [$status, $text] = $this->apiRequest('POST', $content, ['type' => 'text', 'text' => '<p>Moved via API</p>']);

        $this->assertSame(201, $status, json_encode($text, JSON_PRETTY_PRINT));

        [$status, $moved] = $this->apiRequest('POST', $text['@id'].'/move', [
            'target' => (int) basename($group['@id']),
            'position' => 'first',
            'ptable' => 'tl_content',
        ]);

        $this->assertSame(200, $status, json_encode($moved, JSON_PRETTY_PRINT));

        // The text element is now a child of the element group
        $backend = $this->login();
        $backend->visit('/contao?do=article&table=tl_content&id='.$articleId);
        $backend->clickTitlePrefix('Edit the child elements');

        $this->assertSelectorTextContains('.cte_type', 'Text');
        $this->assertStringContainsString('Moved via API', $backend->page()->locator('#main')->textContent());
    }

    public function testCannotMoveAnElementGroupIntoItself(): void
    {
        [, $group] = $this->apiRequest('POST', '/contao/api/dc/article/'.$this->fixtureId('article').'/content', ['type' => 'element_group']);

        [$status] = $this->apiRequest('POST', $group['@id'].'/move', [
            'target' => (int) basename($group['@id']),
            'position' => 'first',
            'ptable' => 'tl_content',
        ]);

        $this->assertGreaterThanOrEqual(400, $status);
        $this->assertLessThan(500, $status);
    }

    /**
     * Unlike tl_content, tl_article has no "target" field that could collide with
     * the "target" of the move request.
     */
    public function testMovesAnArticleToAnotherPage(): void
    {
        $article = '/contao/api/dc/article/'.$this->fixtureId('article');
        $page = $this->fixtureId('page_microsite_home');

        [$status, $moved] = $this->apiRequest('POST', $article.'/move', ['target' => (int) $page, 'position' => 'first']);

        $this->assertSame(200, $status, json_encode($moved, JSON_PRETTY_PRINT));

        [, $record] = $this->apiRequest('GET', $article);
        $pid = $record['pid'];

        // The parent is either returned as a plain ID or as a reference
        $this->assertSame($page, (string) (\is_array($pid) ? basename($pid['@id']) : $pid));
    }
}

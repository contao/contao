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
use PHPUnit\Framework\Attributes\DataProvider;

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

        [$status, $moved] = $this->apiRequest(
            'POST',
            $text['@id'].'/move',
            [
                'target' => (int) basename($group['@id']),
                'position' => 'first',
                'ptable' => 'tl_content',
            ],
        );

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

        [$status] = $this->apiRequest(
            'POST',
            $group['@id'].'/move',
            [
                'target' => (int) basename($group['@id']),
                'position' => 'first',
                'ptable' => 'tl_content',
            ],
        );

        $this->assertGreaterThanOrEqual(400, $status);
        $this->assertLessThan(500, $status);
    }

    /**
     * Unlike tl_content, tl_article has no "target" field that could collide with the
     * "target" of the move request.
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

    #[DataProvider('getSortableResources')]
    public function testMovesARecordAfterASibling(string $collection, array $first, array $second): void
    {
        $collection = $this->interpolate($collection);

        [$status, $a] = $this->apiRequest('POST', $collection, $first);

        $this->assertSame(201, $status, json_encode($a, JSON_PRETTY_PRINT));

        [$status, $b] = $this->apiRequest('POST', $collection, $second);

        $this->assertSame(201, $status, json_encode($b, JSON_PRETTY_PRINT));

        [$status, $moved] = $this->apiRequest('POST', $a['@id'].'/move', ['target' => (int) basename($b['@id']), 'position' => 'after']);

        $this->assertSame(200, $status, json_encode($moved, JSON_PRETTY_PRINT));

        [, $list] = $this->apiRequest('GET', $collection.'?itemsPerPage=300');
        $ids = array_column($this->members($list), '@id');

        $this->assertGreaterThan(array_search($b['@id'], $ids, true), array_search($a['@id'], $ids, true));
    }

    public static function getSortableResources(): iterable
    {
        yield 'faq' => ['/contao/api/dc/faq_category/{faq_category_support}/faq', ['question' => 'First?', 'answer' => '<p>A</p>'], ['question' => 'Second?', 'answer' => '<p>B</p>']];
        yield 'favorites' => ['/contao/api/dc/favorites', ['title' => 'First', 'url' => 'https://example.com/a'], ['title' => 'Second', 'url' => 'https://example.com/b']];
        yield 'form field' => ['/contao/api/dc/form/{form_contact}/form_field', ['type' => 'text', 'name' => 'first'], ['type' => 'text', 'name' => 'second']];
        yield 'image size item' => ['/contao/api/dc/theme/{theme_editorial}/image_size/{image_size_content}/image_size_item', ['media' => '(max-width: 600px)'], ['media' => '(max-width: 400px)']];
    }

    #[DataProvider('getMovableResources')]
    public function testMovesARecordToAnotherParent(string $source, string $target, array $record, string $ptable = ''): void
    {
        [$source, $target] = [$this->interpolate($source), $this->interpolate($target)];

        [$status, $created] = $this->apiRequest('POST', $source, $record);

        $this->assertSame(201, $status, json_encode($created, JSON_PRETTY_PRINT));

        // The target is the ID of the new parent, i.e. the last ID in the target path
        $body = ['target' => (int) basename(\dirname($target)), 'position' => 'first'] + ($ptable ? ['ptable' => $ptable] : []);

        [$status, $moved] = $this->apiRequest('POST', $created['@id'].'/move', $body);

        $this->assertSame(200, $status, json_encode($moved, JSON_PRETTY_PRINT));

        $id = basename($created['@id']);

        [, $list] = $this->apiRequest('GET', $source.'?itemsPerPage=300');

        $this->assertNotContains($id, array_map(basename(...), array_column($this->members($list), '@id')));

        [, $list] = $this->apiRequest('GET', $target.'?itemsPerPage=300');

        $this->assertContains($id, array_map(basename(...), array_column($this->members($list), '@id')));
    }

    public static function getMovableResources(): iterable
    {
        $text = ['type' => 'text', 'text' => '<p>Moved via API</p>'];
        $date = '2026-01-01T10:00:00+00:00';

        yield 'calendar events' => ['/contao/api/dc/calendar/{calendar_events}/calendar_events', '/contao/api/dc/calendar/{calendar_internal}/calendar_events', ['title' => 'Moved event', 'startDate' => $date]];
        yield 'faq' => ['/contao/api/dc/faq_category/{faq_category_support}/faq', '/contao/api/dc/faq_category/{faq_category_products}/faq', ['question' => 'Moved?', 'answer' => '<p>Yes</p>']];
        yield 'form field' => ['/contao/api/dc/form/{form_contact}/form_field', '/contao/api/dc/form/{form_newsletter_signup}/form_field', ['type' => 'text', 'name' => 'moved']];
        yield 'news' => ['/contao/api/dc/news_archive/{news_archive_corporate}/news', '/contao/api/dc/news_archive/{news_archive_press}/news', ['headline' => 'Moved news', 'date' => $date, 'time' => $date]];
        yield 'newsletter' => ['/contao/api/dc/newsletter_channel/{newsletter_channel_marketing}/newsletter', '/contao/api/dc/newsletter_channel/{newsletter_channel_product_updates}/newsletter', ['subject' => 'Moved newsletter']];
        yield 'newsletter recipients' => ['/contao/api/dc/newsletter_channel/{newsletter_channel_marketing}/newsletter_recipients', '/contao/api/dc/newsletter_channel/{newsletter_channel_product_updates}/newsletter_recipients', ['email' => 'moved@example.com']];
        yield 'image size' => ['/contao/api/dc/theme/{theme_editorial}/image_size', '/contao/api/dc/theme/{theme_classic}/image_size', ['name' => 'Moved size']];
        yield 'image size item' => ['/contao/api/dc/theme/{theme_editorial}/image_size/{image_size_content}/image_size_item', '/contao/api/dc/theme/{theme_editorial}/image_size/{image_size_teaser}/image_size_item', ['media' => '(max-width: 600px)']];
        yield 'layout' => ['/contao/api/dc/theme/{theme_editorial}/layout', '/contao/api/dc/theme/{theme_classic}/layout', ['name' => 'Moved layout', 'type' => 'modern', 'template' => 'page/layout']];
        yield 'module' => ['/contao/api/dc/theme/{theme_editorial}/module', '/contao/api/dc/theme/{theme_classic}/module', ['name' => 'Moved module', 'type' => 'html', 'html' => '<p>API</p>']];
        yield 'news content' => ['/contao/api/dc/news_archive/{news_archive_corporate}/news/{news_corporate}/content', '/contao/api/dc/news_archive/{news_archive_corporate}/news/{news_corporate}/content/{content_news_group}/content', $text, 'tl_content'];
        yield 'event content' => ['/contao/api/dc/calendar/{calendar_events}/calendar_events/{event_summer}/content', '/contao/api/dc/calendar/{calendar_events}/calendar_events/{event_summer}/content/{content_event_group}/content', $text, 'tl_content'];
        yield 'theme content' => ['/contao/api/dc/theme/{theme_editorial}/content', '/contao/api/dc/theme/{theme_editorial}/content/{content_theme_group}/content', $text, 'tl_content'];
    }

    public function testMovesAPageIntoAnotherPage(): void
    {
        $page = '/contao/api/dc/page/'.$this->fixtureId('page_main_home');
        $target = $this->fixtureId('page_microsite');

        [$status, $moved] = $this->apiRequest('POST', $page.'/move', ['target' => (int) $target, 'position' => 'first']);

        $this->assertSame(200, $status, json_encode($moved, JSON_PRETTY_PRINT));

        [, $record] = $this->apiRequest('GET', $page);
        $pid = $record['pid'];

        // The parent is either returned as a plain ID or as a reference
        $this->assertSame($target, (string) (\is_array($pid) ? basename($pid['@id'] ?? $pid['iri']) : $pid));
    }
}

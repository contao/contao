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

/**
 * Runs the get_collection, post, get, patch and delete operations of every data
 * container resource in the OpenAPI documentation.
 */
class ResourceTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    private const string PAGE = '/contao/api/dc/page/{page_main_home}';

    private const string DATE = '2026-01-01T10:00:00+00:00';

    #[DataProvider('getResources')]
    public function testCreatesReadsUpdatesAndDeletesRecords(string $collection, array $create, array $update): void
    {
        $collection = $this->interpolate($collection);

        // Relations are IRIs with fixture placeholders, e.g. {"iri": "/contao/api/dc/page/{page_main_home}"}
        array_walk_recursive(
            $create,
            function (&$value): void {
                if (\is_string($value)) {
                    $value = $this->interpolate($value);
                }
            },
        );

        [$status, $record] = $this->apiRequest('POST', $collection, $create);

        $this->assertSame(201, $status, json_encode($record, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $record, json_encode($record, JSON_PRETTY_PRINT));

        [$status, $read] = $this->apiRequest('GET', $record['@id']);

        $this->assertSame(200, $status, json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($record['@id'], $read['@id']);

        [$status, $list] = $this->apiRequest('GET', $collection.'?itemsPerPage=300');

        $this->assertSame(200, $status, json_encode($list, JSON_PRETTY_PRINT));
        $this->assertContains($record['@id'], array_column($this->members($list), '@id'));

        [$status, $patched] = $this->apiRequest('PATCH', $record['@id'], $update);

        $this->assertSame(200, $status, json_encode($patched, JSON_PRETTY_PRINT));

        [, $read] = $this->apiRequest('GET', $record['@id']);

        foreach ($update as $field => $value) {
            $this->assertSame($value, $read[$field]);
        }

        [$status] = $this->apiRequest('DELETE', $record['@id']);

        $this->assertSame(204, $status);

        [$status] = $this->apiRequest('GET', $record['@id']);

        $this->assertSame(404, $status);
    }

    public static function getResources(): iterable
    {
        $text = ['type' => 'text', 'text' => '<p>Created via API</p>'];

        // Top-level resources
        yield 'article' => ['/contao/api/dc/article', ['title' => 'API article'], ['title' => 'Updated']];
        yield 'calendar' => ['/contao/api/dc/calendar', ['title' => 'API calendar', 'jumpTo' => ['iri' => self::PAGE]], ['title' => 'Updated']];
        yield 'faq_category' => ['/contao/api/dc/faq_category', ['title' => 'API FAQ', 'headline' => 'API FAQ'], ['title' => 'Updated']];
        yield 'favorites' => ['/contao/api/dc/favorites', ['title' => 'API favorite', 'url' => 'https://example.com/'], ['title' => 'Updated']];
        yield 'form' => ['/contao/api/dc/form', ['title' => 'API form'], ['title' => 'Updated']];
        yield 'member' => ['/contao/api/dc/member', ['firstname' => 'API', 'lastname' => 'Member', 'email' => 'api.member@example.com'], ['firstname' => 'Updated']];
        yield 'member_group' => ['/contao/api/dc/member_group', ['name' => 'API members'], ['name' => 'Updated']];
        yield 'news_archive' => ['/contao/api/dc/news_archive', ['title' => 'API news', 'jumpTo' => ['iri' => self::PAGE]], ['title' => 'Updated']];
        yield 'newsletter_channel' => ['/contao/api/dc/newsletter_channel', ['title' => 'API channel', 'sender' => 'api@example.com'], ['title' => 'Updated']];
        yield 'page' => ['/contao/api/dc/page', ['type' => 'root', 'title' => 'API root', 'language' => 'de'], ['title' => 'Updated']];
        yield 'theme' => ['/contao/api/dc/theme', ['name' => 'API theme', 'author' => 'Contao'], ['name' => 'Updated']];
        yield 'user' => ['/contao/api/dc/user', ['username' => 'api.user', 'name' => 'API user', 'email' => 'api.user@example.com', 'password' => 'api-password-1234'], ['name' => 'Updated']];
        yield 'user_group' => ['/contao/api/dc/user_group', ['name' => 'API editors'], ['name' => 'Updated']];

        // Nested resources
        yield 'article content' => ['/contao/api/dc/article/{article}/content', $text, ['title' => 'Updated']];
        yield 'calendar events' => ['/contao/api/dc/calendar/{calendar_events}/calendar_events', ['title' => 'API event', 'startDate' => self::DATE], ['title' => 'Updated']];
        yield 'event content' => ['/contao/api/dc/calendar/{calendar_events}/calendar_events/{event_summer}/content', $text, ['title' => 'Updated']];
        yield 'nested event content' => ['/contao/api/dc/calendar/{calendar_events}/calendar_events/{event_summer}/content/{content_event_group}/content', $text, ['title' => 'Updated']];
        yield 'faq' => ['/contao/api/dc/faq_category/{faq_category_support}/faq', ['question' => 'API question?', 'answer' => '<p>Yes</p>'], ['question' => 'Updated?']];
        yield 'form field' => ['/contao/api/dc/form/{form_contact}/form_field', ['type' => 'text', 'name' => 'api_field'], ['name' => 'api_field_updated']];
        yield 'news' => ['/contao/api/dc/news_archive/{news_archive_corporate}/news', ['headline' => 'API news', 'date' => self::DATE, 'time' => self::DATE], ['headline' => 'Updated']];
        yield 'news content' => ['/contao/api/dc/news_archive/{news_archive_corporate}/news/{news_corporate}/content', $text, ['title' => 'Updated']];
        yield 'nested news content' => ['/contao/api/dc/news_archive/{news_archive_corporate}/news/{news_corporate}/content/{content_news_group}/content', $text, ['title' => 'Updated']];
        yield 'newsletter' => ['/contao/api/dc/newsletter_channel/{newsletter_channel_marketing}/newsletter', ['subject' => 'API newsletter'], ['subject' => 'Updated']];
        yield 'newsletter recipients' => ['/contao/api/dc/newsletter_channel/{newsletter_channel_marketing}/newsletter_recipients', ['email' => 'api.recipient@example.com'], ['email' => 'updated@example.com']];
        yield 'theme content' => ['/contao/api/dc/theme/{theme_editorial}/content', $text, ['title' => 'Updated']];
        yield 'nested theme content' => ['/contao/api/dc/theme/{theme_editorial}/content/{content_theme_group}/content', $text, ['title' => 'Updated']];
        yield 'image size' => ['/contao/api/dc/theme/{theme_editorial}/image_size', ['name' => 'API size'], ['name' => 'Updated']];
        yield 'image size item' => ['/contao/api/dc/theme/{theme_editorial}/image_size/{image_size_content}/image_size_item', ['media' => '(max-width: 600px)', 'width' => 600], ['media' => '(max-width: 400px)']];
        yield 'layout' => ['/contao/api/dc/theme/{theme_editorial}/layout', ['name' => 'API layout', 'type' => 'modern', 'template' => 'page/layout'], ['name' => 'Updated']];
        yield 'module' => ['/contao/api/dc/theme/{theme_editorial}/module', ['name' => 'API module', 'type' => 'html', 'html' => '<p>API</p>'], ['name' => 'Updated']];
    }

    public function testListsPreviewLinks(): void
    {
        // Preview links can only be created from a front end preview URL
        [$status, $collection] = $this->apiRequest('GET', '/contao/api/dc/preview_link');

        $this->assertSame(200, $status, json_encode($collection, JSON_PRETTY_PRINT));
        $this->assertSame([], $this->members($collection));
    }
}

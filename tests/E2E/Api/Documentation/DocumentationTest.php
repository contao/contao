<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api\Documentation;

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Api\ApiTestTrait;

class DocumentationTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    public function testOpenApiDocumentationListsTheResources(): void
    {
        [$status, $docs] = $this->apiRequest('GET', '/contao/api/docs.jsonopenapi', accept: 'application/vnd.openapi+json');

        $this->assertSame(200, $status);
        $this->assertArrayHasKey('/contao/api/dc/news_archive', $docs['paths']);
        $this->assertArrayHasKey('/contao/api/dc/article/{article_id}/content', $docs['paths']);
        $this->assertArrayHasKey('/contao/api/files', $docs['paths']);
    }

    public function testDocumentsTheOperationsOfTheResources(): void
    {
        [$status, $docs] = $this->apiRequest('GET', '/contao/api/docs.jsonopenapi', accept: 'application/vnd.openapi+json');

        $this->assertSame(200, $status);

        $expected = [
            '/contao/api/dc/faq_category' => ['get', 'post'],
            '/contao/api/dc/faq_category/{id}' => ['delete', 'get', 'patch'],
            '/contao/api/dc/faq_category/{faq_category_id}/faq' => ['get', 'post'],
            '/contao/api/dc/faq_category/{faq_category_id}/faq/{id}' => ['delete', 'get', 'patch'],
            '/contao/api/dc/faq_category/{faq_category_id}/faq/{id}/move' => ['post'],
            '/contao/api/dc/theme/{theme_id}/image_size/{image_size_id}/image_size_item' => ['get', 'post'],
            '/contao/api/dc/news_archive/{news_archive_id}/news/{news_id}/content/{nested}/content' => ['get', 'post'],
            '/contao/api/files/{path}' => ['get', 'put'],
            '/contao/api/user_templates/{name}' => ['delete', 'get', 'patch'],
            '/contao/api/user_template_operations/create_content_element_variant' => ['post'],
            '/contao/api/user_template_operations/rename_content_element_variant' => ['post'],
        ];

        foreach ($expected as $path => $methods) {
            $this->assertArrayHasKey($path, $docs['paths']);

            $documented = array_intersect(array_keys($docs['paths'][$path]), ['get', 'post', 'put', 'patch', 'delete']);
            sort($documented);

            $this->assertSame($methods, $documented, $path);
        }

        // Only sortable tables can be moved
        $this->assertArrayNotHasKey('/contao/api/dc/faq_category/{id}/move', $docs['paths']);
        $this->assertArrayNotHasKey('/contao/api/dc/news_archive/{id}/move', $docs['paths']);
    }
}

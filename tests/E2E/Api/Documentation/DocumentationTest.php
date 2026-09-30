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
}

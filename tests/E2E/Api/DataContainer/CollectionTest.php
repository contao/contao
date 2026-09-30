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

class CollectionTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    public function testListsRecords(): void
    {
        [$status, $collection] = $this->apiRequest('GET', '/contao/api/dc/news_archive');

        $this->assertSame(200, $status);

        $titles = array_column($this->members($collection), 'title');

        $this->assertContains('Corporate news', $titles);
        $this->assertContains('Press releases', $titles);
    }

    public function testPaginatesRecords(): void
    {
        [$status, $first] = $this->apiRequest('GET', '/contao/api/dc/news_archive?itemsPerPage=1');

        $this->assertSame(200, $status);
        $this->assertCount(1, $this->members($first));

        [$status, $second] = $this->apiRequest('GET', '/contao/api/dc/news_archive?itemsPerPage=1&page=2');

        $this->assertSame(200, $status);
        $this->assertCount(1, $this->members($second));
        $this->assertNotSame($this->members($first)[0]['title'], $this->members($second)[0]['title']);
    }

    public function testReadsARecord(): void
    {
        $id = $this->fixtureId('news_archive_corporate');

        [$status, $archive] = $this->apiRequest('GET', '/contao/api/dc/news_archive/'.$id);

        $this->assertSame(200, $status);
        $this->assertSame('/contao/api/dc/news_archive/'.$id, $archive['@id']);
        $this->assertSame('Corporate news', $archive['title']);
    }

    public function testReturnsNotFoundForAnUnknownRecord(): void
    {
        [$status] = $this->apiRequest('GET', '/contao/api/dc/news_archive/999999');

        $this->assertSame(404, $status);
    }
}

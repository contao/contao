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

class DataContainerTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    public function testCreatedRecordAppearsInTheBackend(): void
    {
        // The redirect page is mandatory. Relations are passed as {"iri": …}, so use
        // the IRI the API returns for the page instead of building it.
        [$status, $page] = $this->apiRequest('GET', '/contao/api/dc/page/'.$this->fixtureId('page_main_home'));

        $this->assertSame(200, $status, json_encode($page, JSON_PRETTY_PRINT));

        [$status, $archive] = $this->apiRequest(
            'POST',
            '/contao/api/dc/news_archive', [
                'title' => 'Created via API',
                'jumpTo' => ['iri' => $page['@id']],
        ],
        );

        $this->assertSame(201, $status, json_encode($archive, JSON_PRETTY_PRINT));

        $backend = $this->login();
        $backend->visit('/contao?do=news');

        $this->assertStringContainsString('Created via API', $backend->page()->locator('#main')->textContent());

        [$status] = $this->apiRequest('PATCH', $archive['@id'], ['title' => 'Updated via API']);

        $this->assertSame(200, $status);

        $backend->visit('/contao?do=news');

        $this->assertStringContainsString('Updated via API', $backend->page()->locator('#main')->textContent());

        [$status] = $this->apiRequest('DELETE', $archive['@id']);

        $this->assertSame(204, $status);

        $backend->visit('/contao?do=news');

        $this->assertStringNotContainsString('Updated via API', $backend->page()->locator('#main')->textContent());
    }

    public function testRejectsARecordWithoutAMandatoryField(): void
    {
        // The title and the redirect page of a news archive are mandatory
        [$status] = $this->apiRequest('POST', '/contao/api/dc/news_archive', []);

        $this->assertSame(422, $status);
    }
}

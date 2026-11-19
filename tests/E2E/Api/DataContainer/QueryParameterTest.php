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

use Contao\E2eTesting\Http\HttpRequest;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

class QueryParameterTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testRejectsAnUnknownQueryParameterOfACollection(): void
    {
        $response = self::managedEdition()->send(HttpRequest::get('/contao/api/dc/page?pid=5')->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
        ]));

        $content = $response->getContent(false);
        $detail = $response->toArray(false)['detail'] ?? '';

        $this->assertSame(400, $response->getStatusCode(), $content);
        $this->assertStringContainsString('"pid"', $detail, $content);
    }

    public function testRejectsAnUnknownQueryParameterOfARecord(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $response = self::managedEdition()->send(HttpRequest::get($fixtures->interpolate('/contao/api/dc/article/{article_main_home}?pid={page_main_home}'))->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
        ]));

        $content = $response->getContent(false);

        $this->assertSame(400, $response->getStatusCode(), $content);
        $this->assertStringContainsString('"pid"', $response->toArray(false)['detail'] ?? '', $content);
    }

    public function testRejectsAnUnknownQueryParameterOfAPatch(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}');

        $headers = [
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
            'Content-Type' => 'application/merge-patch+json',
        ];

        $response = self::managedEdition()->send(HttpRequest::json('PATCH', $path.'?pid=1')->withHeaders($headers)->withJson(['title' => 'Main website home']));
        $content = $response->getContent(false);

        $this->assertSame(400, $response->getStatusCode(), $content);
        $this->assertStringContainsString('"pid"', $response->toArray(false)['detail'] ?? '', $content);

        $response = self::managedEdition()->send(HttpRequest::json('PATCH', $path)->withHeaders($headers)->withJson(['title' => 'Main website home']));
        $content = $response->getContent(false);

        $this->assertSame(200, $response->getStatusCode(), $content);
        $this->assertSame('Main website home', $response->toArray(false)['title'] ?? null, $content);
    }

    public function testAcceptsSortingAndPagination(): void
    {
        $headers = [
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
        ];

        $response = self::managedEdition()->send(HttpRequest::get('/contao/api/dc/user?sort=name&page=1&itemsPerPage=100')->withHeaders($headers));
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));

        $names = array_column($data['member'], 'name');
        $sorted = $names;
        sort($sorted, SORT_STRING | SORT_FLAG_CASE);

        $this->assertGreaterThan(2, \count($names));
        $this->assertSame($sorted, $names);

        $response = self::managedEdition()->send(HttpRequest::get('/contao/api/dc/user?sort=name&page=2&itemsPerPage=2')->withHeaders($headers));
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame(\array_slice($names, 2, 2), array_column($data['member'], 'name'));
    }

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        $composer = self::createMonorepoComposerConfig(
            'api-bundle',
            'calendar-bundle',
            'core-bundle',
            'faq-bundle',
            'news-bundle',
            'newsletter-bundle',
        );

        $recipe = InstallationRecipe::create($composer)
            ->withFixtureFile(self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml')
            ->withFixtureFile(self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml')
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }
}

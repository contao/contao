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
use Symfony\Contracts\HttpClient\ResponseInterface;

class NewsTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testCreatesNewsWithTheCreatePermission(): void
    {
        $response = $this->createNews('user_news_author');

        $this->assertSame(201, $response->getStatusCode(), $response->getContent(false));

        $data = $response->toArray(false);
        $this->assertSame('API news', $data['headline']);

        $response = self::managedEdition()->send(
            HttpRequest::get($data['@id'])
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('user_news_author'),
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('API news', $data['headline']);
    }

    public function testDeniesCreatingNewsWithoutTheCreatePermission(): void
    {
        $response = $this->createNews('user_news_writer');

        $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));

        $path = self::managedEdition()->database()->fixtures()->interpolate('/contao/api/dc/news_archive/{news_archive_corporate}/news');

        $response = self::managedEdition()->send(
            HttpRequest::get($path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('user_news_writer'),
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertNotContains('API news', array_column($data['member'] ?? [], 'headline'));
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

    private function createNews(string $userFixture): ResponseInterface
    {
        $path = self::managedEdition()->database()->fixtures()->interpolate('/contao/api/dc/news_archive/{news_archive_corporate}/news');

        return self::managedEdition()->send(
            HttpRequest::json('POST', $path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken($userFixture),
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/ld+json',
                ])
                ->withJson(['headline' => 'API news']),
        );
    }
}

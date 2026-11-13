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

class NewsArchiveTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testCreatesANewsArchiveWithARedirectPage(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $pageId = (int) $fixtures->value('page_main_home');

        $request = HttpRequest::json('POST', '/contao/api/dc/news_archive')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'title' => 'API news',
                'jumpTo' => ['iri' => $fixtures->interpolate('/contao/api/dc/page/{page_main_home}')],
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($pageId, $data['jumpTo']['id']);
    }

    public function testDeniesUpdatingANewsArchiveWithoutTheUpdatePermission(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/news_archive/{news_archive_corporate}');

        $response = self::managedEdition()->send(
            HttpRequest::json('PATCH', $path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('user_news_writer'),
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/merge-patch+json',
                ])
                ->withJson(['title' => 'Renamed archive']),
        );

        $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));

        $response = self::managedEdition()->send(
            HttpRequest::get($path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('user_news_writer'),
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($fixtures->value('news_archive_corporate', 'title'), $data['title']);
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

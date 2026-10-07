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
use Contao\InstallationRecipe\Fixture\FixtureSet;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

class ArticleTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testCreatesArticleOnPage(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $pageId = (int) $fixtures->value('page_main_home');

        $request = HttpRequest::json('POST', '/contao/api/dc/article')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'pid' => ['iri' => $fixtures->interpolate('/contao/api/dc/page/{page_main_home}')],
                'title' => 'API article',
                'alias' => 'api-article',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($pageId, $data['pid']['id']);

        $request = HttpRequest::get('/contao/api/dc/article')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $collection = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($collection, JSON_PRETTY_PRINT));
        $this->assertContains($data['@id'], array_column($collection['member'] ?? [], '@id'));
    }

    public function testMovesAnArticleIntoAnotherPage(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}');
        $parent = (int) $fixtures->value('page_microsite_home');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'target' => $parent,
                'position' => 'last',
                'ptable' => 'tl_page',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($parent, $data['pid']['id']);

        $response = self::managedEdition()->send(HttpRequest::get($path)->withHeaders([
            'Authorization' => 'Bearer e2e',
            'Accept' => 'application/ld+json',
        ]));

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($parent, $data['pid']['id']);
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

        return ManagedEditionConfig::create(InstallationRecipe::create($composer), self::projectDirectory());
    }
}

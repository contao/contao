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

class PageTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testCreatesASubpage(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $parentId = (int) $fixtures->value('page_main_home');

        $request = HttpRequest::json('POST', '/contao/api/dc/page')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'pid' => ['iri' => $fixtures->interpolate('/contao/api/dc/page/{page_main_home}')],
                'title' => 'API subpage',
                'alias' => 'api-subpage',
                'type' => 'regular',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($parentId, $data['pid']['id']);
    }

    public function testCreatesPage(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $request = HttpRequest::json('POST', '/contao/api/dc/page')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                // Regular pages need a parent as without one, Contao creates would just create a
                // root page draft
                'pid' => ['iri' => $fixtures->interpolate('/contao/api/dc/page/{page_main_website}')],
                'type' => 'regular',
                'title' => 'API page',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame('API page', $data['title']);
    }

    public function testReadsPage(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $path = $fixtures->interpolate('/contao/api/dc/page/{page_main_home}');

        $request = HttpRequest::get($path)
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($path, $data['@id']);
        $this->assertSame('Main website home', $data['title']);
    }

    public function testUpdatesPage(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $path = $fixtures->interpolate('/contao/api/dc/page/{page_microsite_home}');

        $request = HttpRequest::json('PATCH', $path)
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/merge-patch+json',
            ])
            ->withJson(['title' => 'Updated API page'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('Updated API page', $data['title']);

        $response = self::managedEdition()->send(HttpRequest::get($path)->withHeaders([
            'Authorization' => 'Bearer e2e',
            'Accept' => 'application/ld+json',
        ]));

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('Updated API page', $data['title']);
    }

    public function testDeletesPage(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $path = $fixtures->interpolate('/contao/api/dc/page/{page_microsite_home}');

        $request = HttpRequest::create('DELETE', $path)
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);

        $this->assertSame(204, $response->getStatusCode(), $response->getContent(false));

        $response = self::managedEdition()->send(HttpRequest::get($path)->withHeaders([
            'Authorization' => 'Bearer e2e',
            'Accept' => 'application/ld+json',
        ]));

        $this->assertSame(404, $response->getStatusCode(), $response->getContent(false));
    }

    /**
     * The move target is a page ID, not the boolean target field of tl_page.
     */
    public function testMovesAPageIntoAnotherParent(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $path = $fixtures->interpolate('/contao/api/dc/page/{page_microsite_home}');
        $parent = (int) $fixtures->value('page_main_home');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'target' => $parent,
                'position' => 'last',
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

        $recipe = InstallationRecipe::create($composer)
            ->withFixtureFile(self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml')
            ->withFixtureFile(self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml')
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }
}

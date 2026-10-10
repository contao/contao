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

class PreviewLinkTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testDoesNotDocumentCreatingPreviewLinks(): void
    {
        self::managedEdition()->prepareDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $response = self::managedEdition()->send(
            HttpRequest::get('/contao/api/docs.jsonopenapi')
                ->withHeaders([
                    'Authorization' => 'Bearer e2e',
                    'Accept' => 'application/vnd.openapi+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('/contao/api/dc/preview_link', $data['paths']);
        $this->assertArrayHasKey('get', $data['paths']['/contao/api/dc/preview_link']);

        // Preview links are marked as notCreatable in the DCA, so the API documentation
        // must omit POST.
        $this->assertArrayNotHasKey('post', $data['paths']['/contao/api/dc/preview_link']);
        $this->assertArrayHasKey('post', $data['paths']['/contao/api/dc/page']);
    }

    public function testReadsAPreviewLink(): void
    {
        $fixtures = self::managedEdition()->prepareDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $path = $fixtures->interpolate('/contao/api/dc/preview_link/{preview_link}');
        $response = self::managedEdition()->send(
            HttpRequest::get($path)
                ->withHeaders([
                    'Authorization' => 'Bearer e2e',
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($path, $data['@id']);
        $this->assertSame('https://example.test/preview', $data['url']);
        $this->assertFalse($data['published']);
    }

    public function testUpdatesAPreviewLink(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $path = $fixtures->interpolate('/contao/api/dc/preview_link/{preview_link}');
        $response = self::managedEdition()->send(
            HttpRequest::json('PATCH', $path)
                ->withHeaders([
                    'Authorization' => 'Bearer e2e',
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/merge-patch+json',
                ])
                ->withJson(['published' => true]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertTrue($data['published']);

        $response = self::managedEdition()->send(
            HttpRequest::get($data['@id'])
                ->withHeaders([
                    'Authorization' => 'Bearer e2e',
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertTrue($data['published']);
    }

    public function testDeletesAPreviewLink(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $path = $fixtures->interpolate('/contao/api/dc/preview_link/{preview_link}');
        $response = self::managedEdition()->send(
            HttpRequest::create('DELETE', $path)
                ->withHeaders([
                    'Authorization' => 'Bearer e2e',
                    'Accept' => 'application/ld+json',
                ]),
        );

        $this->assertSame(204, $response->getStatusCode(), $response->getContent(false));

        $response = self::managedEdition()->send(
            HttpRequest::get($path)
                ->withHeaders([
                    'Authorization' => 'Bearer e2e',
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(404, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('Not Found', $data['detail']);
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

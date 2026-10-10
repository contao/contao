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

class ContentTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testIdentifiesRecordsInsideElementGroups(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        // Create the element group in the article.
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}/content');

        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'element_group'])
        ;

        $response = self::managedEdition()->send($request);
        $group = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($group, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $group);

        // Nest another group using the first group's returned IRI
        $request = HttpRequest::json('POST', $group['@id'].'/content')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'element_group'])
        ;

        $response = self::managedEdition()->send($request);
        $innerGroup = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($innerGroup, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $innerGroup);
        $this->assertSame($group['@id'], $innerGroup['pid']['@id'] ?? null);

        // Add content in the second element group and check the parent reference.
        $request = HttpRequest::json('POST', $innerGroup['@id'].'/content')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'type' => 'headline',
                'headline' => ['unit' => 'h2', 'value' => 'Nested'],
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($innerGroup['@id'], $data['pid']['@id'] ?? null);

        // Read the content through the returned IRI to verify the full nested route
        $request = HttpRequest::get($data['@id'])
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $read = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($data['@id'], $read['@id']);
        $this->assertSame($innerGroup['@id'], $read['pid']['@id'] ?? null);
        $this->assertSame('Nested', $read['headline']['value']);
    }

    public function testCreatesNestedContentAfterAPrecedingTextElement(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}/content');

        // Add text first so the group ID differs from the article ID.
        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'text', 'text' => '<p>Preceding content</p>'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('text', $data['type']);

        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'element_group'])
        ;

        $response = self::managedEdition()->send($request);
        $group = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($group, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $group);
        $this->assertSame('element_group', $group['type']);

        $request = HttpRequest::json('POST', $group['@id'].'/content')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'text', 'text' => '<p>Nested content</p>'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($group['@id'], $data['pid']['@id'] ?? null);

        $request = HttpRequest::get($data['@id'])
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $read = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($data['@id'], $read['@id']);
        $this->assertSame($group['@id'], $read['pid']['@id'] ?? null);
        $this->assertSame('<p>Nested content</p>', $read['text']);
    }

    public function testRejectsAParentInThePayloadOfANestedRoute(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}/content');

        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'pid' => ['iri' => $fixtures->interpolate('/contao/api/dc/article/{article_main_home}')],
                'type' => 'text',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(422, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertStringContainsString('given by the route', $data['detail'] ?? '');
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

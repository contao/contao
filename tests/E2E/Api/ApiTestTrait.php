<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api;

use Contao\E2eTesting\Http\Origin;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\InstallationRecipe\Fixture\FixtureResult;
use Contao\InstallationRecipe\Fixture\FixtureSet;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

trait ApiTestTrait
{
    private const string FIXTURE_USERS = 'users.yaml';

    private const string FIXTURE_DEFAULT = 'default.yaml';

    private const string FIXTURE_RESOURCES = 'resources.yaml';

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
            ->withFixtureFile(self::fixtureDirectory().'/'.self::FIXTURE_USERS)
            ->withFixtureFile(self::fixtureDirectory().'/'.self::FIXTURE_DEFAULT)
            ->withFixtureFile(self::apiFixtureDirectory().'/'.self::FIXTURE_RESOURCES)
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }

    private static function fixtureDirectory(): string
    {
        return self::projectDirectory().'/tests/E2E/Fixtures/Backend';
    }

    private static function apiFixtureDirectory(): string
    {
        return self::projectDirectory().'/tests/E2E/Fixtures/Api';
    }

    private function apiFixtures(): FixtureResult
    {
        return self::managedEdition()->prepareDatabase(new FixtureSet([
            self::fixtureDirectory().'/'.self::FIXTURE_USERS,
            self::fixtureDirectory().'/'.self::FIXTURE_DEFAULT,
            self::apiFixtureDirectory().'/'.self::FIXTURE_RESOURCES,
        ]));
    }

    /**
     * @return array{int, array<string, mixed>}
     *
     * @throws \JsonException
     */
    private function apiRequest(string $method, string $path, array|string|null $body = null): array
    {
        $browser = self::managedEdition()->createHttpBrowser(Origin::http('example.test'));

        if (!str_starts_with($path, '/contao/api/')) {
            $path = '/contao/api'.$path;
        }

        $contentType = 'PATCH' === $method
            ? 'application/merge-patch+json'
            : 'application/ld+json';

        $content = null === $body ? null : json_encode($body, JSON_THROW_ON_ERROR);

        $browser->request(
            $method,
            $path,
            server: [
                'CONTENT_TYPE' => $contentType,
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer e2e',
            ],
            content: $content,
        );

        $response = $browser->getInternalResponse();

        return [
            $response->getStatusCode(),
            json_decode($response->getContent(), true) ?? [],
        ];
    }
}

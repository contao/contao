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

use Contao\E2eTesting\Browser\BackendBrowser;
use Contao\E2eTesting\Http\Origin;
use Contao\E2eTesting\ManagedEdition\ManagedEdition;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\InstallationRecipe\Composer\ComposerConfig;
use Contao\InstallationRecipe\Fixture\FixtureSet;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

trait ApiTestTrait
{
    private const string FIXTURE_USERS = 'users.yaml';

    private const string FIXTURE_DEFAULT = 'default.yaml';

    private const string FIXTURE_ARTICLE = 'article.yaml';

    // Provided by AbstractContaoMonorepoE2ETestCase and its ManagedEditionTestTrait
    abstract protected static function projectDirectory(): string;

    abstract protected static function createMonorepoComposerConfig(string ...$bundles): ComposerConfig;

    abstract protected static function managedEdition(): ManagedEdition;

    protected static function createManagedEditionConfig(): ManagedEditionConfig
    {
        // The fixtures fill tables of the calendar, FAQ, news and newsletter bundles
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
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }

    private static function fixtureDirectory(): string
    {
        return self::projectDirectory().'/tests/E2E/Fixtures/Backend';
    }

    /**
     * Returns the ID of a fixture record. Calling it again does not reset the
     * database, so records created in between are kept.
     */
    private function fixtureId(string $fixture): string
    {
        return self::managedEdition()
            ->prepareDatabase(new FixtureSet([
                self::fixtureDirectory().'/'.self::FIXTURE_USERS,
                self::fixtureDirectory().'/'.self::FIXTURE_DEFAULT,
                self::fixtureDirectory().'/'.self::FIXTURE_ARTICLE,
            ]))
            ->interpolate('{'.$fixture.'}')
            ;
    }

    /**
     * Logs in to the back end to check the result of API requests.
     */
    private function login(): BackendBrowser
    {
        $backend = self::managedEdition()->createBackendBrowser();
        $backend->visit('/contao/login');
        $backend->submitLogin('k.jones', 'kevinjones');
        $backend->waitFor('h1');

        return $backend;
    }

    /**
     * Returns the items of a collection response.
     *
     * @return list<array<mixed>>
     */
    private function members(array $collection): array
    {
        return $collection['member'] ?? $collection['hydra:member'] ?? [];
    }

    /**
     * Sends a request to the back end API and returns the status code and the
     * decoded response. Every token is currently authenticated as k.jones.
     *
     * @param array<mixed>|string|null $body
     *
     * @return array{int, array<mixed>}
     */
    private function apiRequest(string $method, string $path, array|string|null $body = null, string|null $contentType = null, string|null $token = 'e2e', string $accept = 'application/ld+json'): array
    {
        $server = [
            'HTTP_ACCEPT' => $accept,
            'CONTENT_TYPE' => $contentType ?? ('PATCH' === $method ? 'application/merge-patch+json' : 'application/ld+json'),
        ];

        if (null !== $token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        $browser = self::managedEdition()->createHttpBrowser(Origin::http('example.test'));
        $browser->request($method, $path, [], [], $server, \is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body);

        $response = $browser->getInternalResponse();

        return [$response->getStatusCode(), json_decode($response->getContent(), true) ?? []];
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api\Documentation;

use Contao\E2eTesting\Http\HttpRequest;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

/**
 * The MCP server reads this document, so a failing schema also fails every MCP
 * operation description.
 */
class RecordDependentOptionsTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testDocumentsTheApiDespiteRecordDependentOptions(): void
    {
        $response = self::managedEdition()->send(
            HttpRequest::get('/contao/api/docs.jsonopenapi')
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                    'Accept' => 'application/vnd.openapi+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('/contao/api/dc/page', $data['paths']);
    }

    public function testDocumentsAndReadsTheFieldWithoutItsOptions(): void
    {
        $response = self::managedEdition()->send(
            HttpRequest::get('/contao/api/docs.jsonopenapi')
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                    'Accept' => 'application/vnd.openapi+json',
                ]),
        );

        $data = $response->toArray(false);
        $fields = array_filter(array_column($data['components']['schemas'] ?? [], 'properties'), static fn (array $properties): bool => isset($properties['record_dependent_select']));

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertNotEmpty($fields);

        foreach ($fields as $properties) {
            $this->assertArrayNotHasKey('enum', $properties['record_dependent_select']);
        }

        $fixtures = self::managedEdition()->database()->fixtures();

        $response = self::managedEdition()->send(
            HttpRequest::get($fixtures->interpolate('/contao/api/dc/article/{article_main_home}'))
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('record_dependent_select', $data);
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

        return ManagedEditionConfig::create($recipe, self::projectDirectory())
            ->withDcaFile(self::projectDirectory().'/tests/E2E/Fixtures/RecordDependentOptions/tl_article.php')
        ;
    }
}

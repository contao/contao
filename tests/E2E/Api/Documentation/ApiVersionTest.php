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
use Contao\E2eTests\Backend\BackendTestTrait;
use Contao\InstallationRecipe\File\FileMapping;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

class ApiVersionTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testSelectorChangesTheDocumentedSchemaAndTheApiContract(): void
    {
        $backend = $this->login();
        $backend->visit('/contao/api/docs');
        $backend->page()->locator('#api-version-form')->waitFor(['state' => 'visible']);

        // Switch back to the first date to catch metadata leaking between versions.
        foreach ([['2026-02-28', 'legacyName', 'Legacy example'], ['2026-03-01', 'displayName', 'Modern example'], ['2026-02-28', 'legacyName', 'Legacy example']] as [$date, $property, $value]) {
            $backend->page()->locator('#api-version')->fill($date);
            $backend->page()->locator('#api-version-form button')->click();
            $backend->page()->getByRole('heading', ['name' => 'Contao API ('.$date.')', 'exact' => true])->waitFor(['state' => 'visible']);
            $backend->page()->locator('#swagger-ui')->getByText('"'.$property.'"', ['exact' => true])->first()->waitFor(['state' => 'visible']);

            $headers = ['Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'), 'Contao-Api-Version' => $date];

            $response = $backend->browser()->context()->request()->get(
                $backend->browser()->uri('/contao/api/docs.jsonopenapi'),
                ['headers' => ['Contao-Api-Version' => $date]],
            );

            $spec = $response->json();

            $this->assertSame(200, $response->status(), json_encode($spec, JSON_PRETTY_PRINT));
            $this->assertSame($date, $spec['info']['version']);

            $schema = $spec['paths']['/contao/api/versioned-example']['get']['responses']['200']['content']['application/json']['schema'];
            $this->assertSame([$property => ['type' => 'string']], $schema['properties']);

            $response = self::managedEdition()->send(HttpRequest::get('/contao/api/versioned-example')->withHeaders($headers));
            $result = $response->toArray(false);

            $this->assertSame(200, $response->getStatusCode(), json_encode($result, JSON_PRETTY_PRINT));
            $this->assertSame([$property => $value], $result);
        }
    }

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        $composer = self::createMonorepoComposerConfig('api-bundle', 'core-bundle')
            ->withOverrides(['autoload' => ['psr-4' => ['App\\' => 'src/']]])
        ;

        $fixtures = self::projectDirectory().'/tests/E2E/Fixtures';

        $recipe = InstallationRecipe::create($composer)
            ->withFixtureFile($fixtures.'/Backend/users.yaml')
            ->withConfigFile($fixtures.'/Backend/security.yaml')
            ->withConfigFile($fixtures.'/ApiVersion/config.yaml')
            ->withFileMapping(new FileMapping($fixtures.'/ApiVersion/VersionedExample.php', 'src/Api/VersionedExample.php'))
            ->withFileMapping(new FileMapping($fixtures.'/ApiVersion/VersionedExampleProvider.php', 'src/Api/VersionedExampleProvider.php'))
            ->withFileMapping(new FileMapping($fixtures.'/ApiVersion/VersionedExampleNormalizer.php', 'src/Api/VersionedExampleNormalizer.php'))
            ->withFileMapping(new FileMapping($fixtures.'/ApiVersion/VersionedExampleOpenApiFactory.php', 'src/Api/VersionedExampleOpenApiFactory.php'))
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }
}

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

/**
 * The back end create only writes fields with a default, so an unsent virtual
 * field stays unset.
 */
class VirtualFieldTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testLeavesUnsentVirtualFieldsUnset(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $request = HttpRequest::get($fixtures->interpolate('/contao/api/dc/article/{article_main_home}'))
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $existing = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($existing, JSON_PRETTY_PRINT));
        $this->assertArrayNotHasKey('virtual_width', $existing);

        $request = HttpRequest::json('POST', '/contao/api/dc/article')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'pid' => ['iri' => $fixtures->interpolate('/contao/api/dc/page/{page_main_home}')],
                'title' => 'Virtual field article',
                'alias' => 'virtual-field-article',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $created = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($created, JSON_PRETTY_PRINT));

        $request = HttpRequest::get($created['@id'])
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertNull($data['virtual_width'] ?? null, 'The create stored a value for a field it was not sent.');
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
            ->withDcaFile(self::projectDirectory().'/tests/E2E/Fixtures/VirtualFields/tl_article.php')
        ;
    }
}

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

class LayoutTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testCreatesModernLayoutWithTemplate(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $request = HttpRequest::json('POST', $fixtures->interpolate('/contao/api/dc/theme/{theme_editorial}/layout'))
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'name' => 'Modern layout',
                'type' => 'modern',
                'template' => 'page/layout',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame((int) $fixtures->value('theme_editorial'), $data['pid']['id']);
        $this->assertSame('modern', $data['type']);
        $this->assertSame('page/layout', $data['template']);

        $response = self::managedEdition()->send(HttpRequest::get($data['@id'])->withHeaders([
            'Authorization' => 'Bearer e2e',
            'Accept' => 'application/ld+json',
        ]));

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('Modern layout', $data['name']);
        $this->assertSame('modern', $data['type']);
        $this->assertSame('page/layout', $data['template']);
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

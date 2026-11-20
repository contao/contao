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

class FormTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testDeniesDeletingAFormWithoutTheDeletePermission(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/form/{form_contact}');

        $response = self::managedEdition()->send(
            HttpRequest::create('DELETE', $path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('user_form_editor'),
                    'Accept' => 'application/ld+json',
                ]),
        );

        $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));
        $this->assertSame($fixtures->value('form_contact', 'title'), $this->readForm($path)['title']);
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

    private function readForm(string $path): array
    {
        $response = self::managedEdition()->send(
            HttpRequest::get($path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('user_form_editor'),
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }
}

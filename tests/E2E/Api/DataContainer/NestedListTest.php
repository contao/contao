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

class NestedListTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testReadsTheItemsOfANestedList(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        // The fixture list stores nested rows ({item, list}) as the list wizard does
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_microsite_home}/content/{content_list}');

        $response = self::managedEdition()->send(HttpRequest::get($path)->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
        ]));

        $data = $response->toArray(false);
        $items = json_encode($data['listitems'] ?? null);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertStringNotContainsString('"Array"', $items);

        foreach (['Foo', 'Bar', 'Baz', 'Qux', 'Quux'] as $item) {
            $this->assertStringContainsString('"'.$item.'"', $items);
        }
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

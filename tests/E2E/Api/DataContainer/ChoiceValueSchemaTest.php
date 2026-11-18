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
use PHPUnit\Framework\Attributes\DataProvider;

class ChoiceValueSchemaTest extends AbstractContaoMonorepoE2ETestCase
{
    #[DataProvider('getChoiceValuesReturnedByTheApi')]
    public function testAcceptsTheChoiceValuesTheApiReturns(string $field, string $page): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/page/{'.$page.'}');

        $response = self::managedEdition()->send(HttpRequest::get($path)->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
        ]));

        $record = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($record, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey($field, $record);

        // Send back the value the API has just returned
        $request = HttpRequest::json('PATCH', $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/merge-patch+json',
            ])
            ->withJson([$field => $record[$field]])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($record[$field], $data[$field]);
    }

    public static function getChoiceValuesReturnedByTheApi(): iterable
    {
        // tl_page.robots has no blank option, but its column default is ""
        yield 'robots' => ['robots', 'page_main_home'];

        // useSSL is a boolean column with associative options
        yield 'useSSL' => ['useSSL', 'page_main_website'];
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

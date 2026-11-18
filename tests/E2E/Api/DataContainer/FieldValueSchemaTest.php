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

class FieldValueSchemaTest extends AbstractContaoMonorepoE2ETestCase
{
    #[DataProvider('getValuesTheBackEndAccepts')]
    public function testAcceptsValuesTheBackEndAccepts(string $parentPath, array $payload, string $field): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $request = HttpRequest::json('POST', $fixtures->interpolate($parentPath))
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson($payload)
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($payload[$field], $data[$field]);
    }

    public function testAcceptsNullForANullableColumn(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/theme/{theme_editorial}/image_size/{image_size_content}');

        $request = HttpRequest::json('PATCH', $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/merge-patch+json',
            ])
            ->withJson(['height' => null])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertNull($data['height']);
    }

    public static function getValuesTheBackEndAccepts(): iterable
    {
        yield 'insert tag in a hyperlink URL' => [
            '/contao/api/dc/article/{article_main_home}/content',
            ['type' => 'hyperlink', 'url' => '{{link_url::1}}', 'linkTitle' => 'Insert tag'],
            'url',
        ];

        yield 'relative hyperlink URL' => [
            '/contao/api/dc/article/{article_main_home}/content',
            ['type' => 'hyperlink', 'url' => '/main-home', 'linkTitle' => 'Relative path'],
            'url',
        ];

        yield 'decimal in a digit field' => [
            '/contao/api/dc/form/{form_contact}/form_field',
            ['type' => 'range', 'name' => 'volume', 'minval' => '0', 'maxval' => '1', 'step' => '0.5'],
            'step',
        ];
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

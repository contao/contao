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

class RecordCreationTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testKeepsTheCustomTemplateOfANewElement(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        // Create a variant of a type other than the default "text" type
        $request = HttpRequest::json('POST', '/contao/api/user_template_operations/create_content_element_variant')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'name' => 'content_element/headline',
                'parameters' => ['identifier_fragment' => 'api_variant'],
            ])
        ;

        $response = self::managedEdition()->send($request);
        $variant = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($variant, JSON_PRETTY_PRINT));
        $this->assertSame('content_element/headline/api_variant', $variant['identifier']);

        try {
            $request = HttpRequest::json('POST', $fixtures->interpolate('/contao/api/dc/article/{article_main_home}/content'))
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/ld+json',
                ])
                ->withJson([
                    'type' => 'headline',
                    'headline' => ['unit' => 'h2', 'value' => 'Variant'],
                    'customTpl' => 'content_element/headline/api_variant',
                ])
            ;

            $response = self::managedEdition()->send($request);
            $data = $response->toArray(false);

            $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
            $this->assertSame('content_element/headline/api_variant', $data['customTpl']);
        } finally {
            $response = self::managedEdition()->send(
                HttpRequest::create('DELETE', '/contao/api/user_templates/content_element/headline/api_variant')->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                    'Accept' => 'application/ld+json',
                ]),
            );

            $this->assertContains($response->getStatusCode(), [200, 204], $response->getContent(false));
        }
    }

    public function testCreatesTheMainArticleOfANewPage(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $request = HttpRequest::json('POST', '/contao/api/dc/page')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'pid' => ['iri' => $fixtures->interpolate('/contao/api/dc/page/{page_microsite}')],
                'title' => 'Page with article',
                'type' => 'regular',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $page = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($page, JSON_PRETTY_PRINT));

        $response = self::managedEdition()->send(HttpRequest::get('/contao/api/dc/article?itemsPerPage=100')->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
        ]));

        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));

        $articles = array_values(array_filter(
            $data['member'] ?? [],
            static fn (array $article): bool => $page['@id'] === ($article['pid']['@id'] ?? null),
        ));

        // The back end creates one article in the layout's main column
        $this->assertCount(1, $articles, json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('main', $articles[0]['inColumn']);
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

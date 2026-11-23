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

class ContentTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testIdentifiesRecordsInsideElementGroups(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        // Create the element group in the article.
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}/content');

        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'element_group'])
        ;

        $response = self::managedEdition()->send($request);
        $group = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($group, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $group);

        // Nest another group using the first group's returned IRI
        $request = HttpRequest::json('POST', $group['@id'].'/content')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'element_group'])
        ;

        $response = self::managedEdition()->send($request);
        $innerGroup = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($innerGroup, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $innerGroup);
        $this->assertSame($group['@id'], $innerGroup['pid']['@id'] ?? null);

        // Add content in the second element group and check the parent reference.
        $request = HttpRequest::json('POST', $innerGroup['@id'].'/content')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'type' => 'headline',
                'headline' => ['unit' => 'h2', 'value' => 'Nested'],
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($innerGroup['@id'], $data['pid']['@id'] ?? null);

        // Read the content through the returned IRI to verify the full nested route
        $request = HttpRequest::get($data['@id'])
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $read = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($data['@id'], $read['@id']);
        $this->assertSame($innerGroup['@id'], $read['pid']['@id'] ?? null);
        $this->assertSame('Nested', $read['headline']['value']);
    }

    public function testCreatesNestedContentAfterAPrecedingTextElement(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}/content');

        // Add text first so the group ID differs from the article ID.
        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'text', 'text' => '<p>Preceding content</p>'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('text', $data['type']);

        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'element_group'])
        ;

        $response = self::managedEdition()->send($request);
        $group = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($group, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $group);
        $this->assertSame('element_group', $group['type']);

        $request = HttpRequest::json('POST', $group['@id'].'/content')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'text', 'text' => '<p>Nested content</p>'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame($group['@id'], $data['pid']['@id'] ?? null);

        $request = HttpRequest::get($data['@id'])
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $read = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($data['@id'], $read['@id']);
        $this->assertSame($group['@id'], $read['pid']['@id'] ?? null);
        $this->assertSame('<p>Nested content</p>', $read['text']);
    }

    public function testRejectsAParentInThePayloadOfANestedRoute(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}/content');

        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'pid' => ['iri' => $fixtures->interpolate('/contao/api/dc/article/{article_main_home}')],
                'type' => 'text',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(422, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertStringContainsString('given by the route', $data['detail'] ?? '');
    }

    public function testRejectsAParentTableInThePayloadOfANestedRoute(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}/content');

        $request = HttpRequest::json('POST', $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'type' => 'text',
                'text' => '<p>Text</p>',
                'ptable' => 'tl_foo',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(422, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertStringContainsString('given by the route', $data['detail'] ?? '');
    }

    public function testRejectsAnUnknownParentTableOnMove(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $article = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}');

        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_foo}');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'target' => (int) $fixtures->value('article_main_content', 'id'),
                'ptable' => 'tl_foo',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(422, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertStringContainsString('is not supported by this data container', $data['detail'] ?? '');

        // The element stays in the article
        $request = HttpRequest::get($path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $read = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($article, $read['pid']['@id'] ?? null);
    }

    public function testRejectsATableThatIsNotAParentOfContentElementsOnMove(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $article = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}');

        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_foo}');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'target' => (int) $fixtures->value('article_main_content', 'id'),
                'ptable' => 'tl_page',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(422, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertStringContainsString('is not supported by this data container', $data['detail'] ?? '');

        // The element stays in the article
        $request = HttpRequest::get($path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $read = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($article, $read['pid']['@id'] ?? null);
    }

    public function testLooksUpTheTargetIdInTheDefaultParentTable(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_foo}');

        // Without "ptable", the target ID is looked up in tl_article, which has no
        // record 999999
        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['target' => 999999])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(404, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertStringContainsString('send "ptable"', $data['detail'] ?? '');
    }

    public function testMovesAnElementIntoAnElementGroupWithAParentTable(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_foo}');
        $group = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_bar}');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['target' => (int) $fixtures->value('content_bar', 'id'), 'ptable' => 'tl_content'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($group, $data['pid']['@id'] ?? null);
    }

    public function testMovesAnElementIntoAnElementGroupByIri(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_foo}');
        $group = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_bar}');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['target' => $group])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($group, $data['pid']['@id'] ?? null);
    }

    public function testMovesAnElementOutOfAnElementGroupByIri(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_bar}/content/{content_baz}');
        $article = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['target' => $article])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($article, $data['pid']['@id'] ?? null);
    }

    public function testDeniesCreatingContentInANewsWithoutAccessToTheNewsModule(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $news = $fixtures->interpolate('/contao/api/dc/news_archive/{news_archive_corporate}/news/{news_foo}');

        $request = HttpRequest::json('POST', $news.'/content')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('user_content_editor'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['type' => 'text', 'text' => '<p>Text</p>'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(403, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
    }

    public function testDeniesMovingContentIntoANewsWithoutAccessToTheNewsModule(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_foo}');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('user_content_editor'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['target' => (int) $fixtures->value('news_foo', 'id'), 'ptable' => 'tl_news'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(403, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));

        // The element stays in the article
        $request = HttpRequest::get($path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $read = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($fixtures->interpolate('/contao/api/dc/article/{article_main_content}'), $read['pid']['@id'] ?? null);
    }

    public function testDeniesMovingContentIntoANewsByIriWithoutAccessToTheNewsModule(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_foo}');
        $news = $fixtures->interpolate('/contao/api/dc/news_archive/{news_archive_corporate}/news/{news_foo}');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('user_content_editor'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['target' => $news, 'ptable' => 'tl_news'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(403, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));

        // The element stays in the article
        $request = HttpRequest::get($path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $read = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($read, JSON_PRETTY_PRINT));
        $this->assertSame($fixtures->interpolate('/contao/api/dc/article/{article_main_content}'), $read['pid']['@id'] ?? null);
    }

    public function testMovesContentIntoANewsWithAccessToTheNewsModule(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $path = $fixtures->interpolate('/contao/api/dc/article/{article_main_content}/content/{content_foo}');
        $news = $fixtures->interpolate('/contao/api/dc/news_archive/{news_archive_corporate}/news/{news_foo}');

        $request = HttpRequest::json('POST', $path.'/move')
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson(['target' => $news, 'ptable' => 'tl_news'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($news, $data['pid']['@id'] ?? null);
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
            ->withFixtureFile(self::projectDirectory().'/tests/E2E/Fixtures/Backend/content.yaml')
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api\UserTemplate;

use Contao\E2eTesting\Http\HttpRequest;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

class UserTemplateTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testReturnsNotFoundForAnUnknownTemplate(): void
    {
        $response = self::managedEdition()->send(
            HttpRequest::get('/contao/api/user_templates/unknown_template')
                ->withHeaders([
                    'Authorization' => 'Bearer e2e',
                    'Accept' => 'application/ld+json',
                ]),
        );

        $data = $response->toArray(false);

        $this->assertSame(404, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('Given identifier does not exist.', $data['detail']);
    }

    public function testRejectsDeletingABundleTemplate(): void
    {
        $path = '/contao/api/user_templates/content_element/text';
        $headers = ['Authorization' => 'Bearer e2e', 'Accept' => 'application/ld+json'];
        $read = HttpRequest::get($path)->withHeaders($headers);

        $response = self::managedEdition()->send($read);
        $before = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($before, JSON_PRETTY_PRINT));
        $this->assertFalse($before['can_edit']);

        $request = HttpRequest::create('DELETE', $path)->withHeaders($headers);

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(422, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('The operation is not available for this template.', $data['detail']);

        $response = self::managedEdition()->send($read);
        $after = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($after, JSON_PRETTY_PRINT));
        $this->assertSame($before['templates'], $after['templates']);
        $this->assertSame($before['operations'], $after['operations']);
    }

    public function testRejectsSavingABundleTemplate(): void
    {
        $path = '/contao/api/user_templates/content_element/text';
        $headers = ['Authorization' => 'Bearer e2e', 'Accept' => 'application/ld+json'];
        $read = HttpRequest::get($path)->withHeaders($headers);

        $response = self::managedEdition()->send($read);
        $before = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($before, JSON_PRETTY_PRINT));
        $this->assertFalse($before['can_edit']);

        $request = HttpRequest::json('PATCH', $path)
            ->withHeaders($headers)
            ->withHeader('Content-Type', 'application/merge-patch+json')
            ->withJson(['code' => 'Changed template'])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(422, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('The operation is not available for this template.', $data['detail']);

        $response = self::managedEdition()->send($read);
        $after = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($after, JSON_PRETTY_PRINT));
        $this->assertSame($before['templates'], $after['templates']);
        $this->assertSame($before['operations'], $after['operations']);
    }

    public function testRejectsRenamingABundleTemplate(): void
    {
        $path = '/contao/api/user_templates/content_element/text';
        $headers = ['Authorization' => 'Bearer e2e', 'Accept' => 'application/ld+json'];
        $read = HttpRequest::get($path)->withHeaders($headers);

        $response = self::managedEdition()->send($read);
        $before = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($before, JSON_PRETTY_PRINT));
        $this->assertFalse($before['can_edit']);

        $request = HttpRequest::json('POST', '/contao/api/user_template_operations/rename_content_element_variant')
            ->withHeaders($headers)
            ->withHeader('Content-Type', 'application/ld+json')
            ->withJson([
                'name' => 'content_element/text',
                'parameters' => ['identifier_fragment' => 'renamed_api_operation'],
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(422, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame('The operation is not available for this template.', $data['detail']);

        $response = self::managedEdition()->send($read);
        $after = $response->toArray(false);

        $this->assertSame(200, $response->getStatusCode(), json_encode($after, JSON_PRETTY_PRINT));
        $this->assertSame($before['templates'], $after['templates']);
        $this->assertSame($before['operations'], $after['operations']);
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

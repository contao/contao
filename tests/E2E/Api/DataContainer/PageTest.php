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
use Contao\InstallationRecipe\Fixture\FixtureSet;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

class PageTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testCreatesASubpage(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml',
            self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml',
        ]));

        $parentId = (int) $fixtures->value('page_main_home');

        $request = HttpRequest::json('POST', '/contao/api/dc/page')
            ->withHeaders([
                'Authorization' => 'Bearer e2e',
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
            ->withJson([
                'pid' => ['iri' => $fixtures->interpolate('/contao/api/dc/page/{page_main_home}')],
                'title' => 'API subpage',
                'alias' => 'api-subpage',
                'type' => 'regular',
            ])
        ;

        $response = self::managedEdition()->send($request);
        $data = $response->toArray(false);

        $this->assertSame(201, $response->getStatusCode(), json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($parentId, $data['pid']['id']);
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

        return ManagedEditionConfig::create(InstallationRecipe::create($composer), self::projectDirectory());
    }
}

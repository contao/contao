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

class ElementGroupMoveTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testMovesAnElementIntoAnElementGroup(): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();
        $article = $fixtures->interpolate('/contao/api/dc/article/{article_main_home}');

        [$status, $group] = $this->sendJson('POST', $article.'/content', ['type' => 'element_group']);
        $this->assertSame(201, $status, json_encode($group, JSON_PRETTY_PRINT));

        [$status, $text] = $this->sendJson('POST', $article.'/content', ['type' => 'text', 'text' => '<p>Moved</p>']);
        $this->assertSame(201, $status, json_encode($text, JSON_PRETTY_PRINT));

        // Without "ptable", the target ID is a record of the default parent table
        [$status, $data] = $this->sendJson('POST', $text['@id'].'/move', ['target' => 999999, 'position' => 'last']);
        $this->assertSame(404, $status, json_encode($data, JSON_PRETTY_PRINT));
        $this->assertStringContainsString('send "ptable"', (string) ($data['detail'] ?? ''));

        $groupId = (int) basename((string) $group['@id']);

        [$status, $data] = $this->sendJson('POST', $text['@id'].'/move', ['target' => $groupId, 'position' => 'last', 'ptable' => 'tl_content']);
        $this->assertSame(200, $status, json_encode($data, JSON_PRETTY_PRINT));
        $this->assertSame($group['@id'], $data['pid']['@id'] ?? null);

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

    private function sendJson(string $method, string $path, array|null $json = null): array
    {
        $request = HttpRequest::json($method, $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
            ])
        ;

        if (null !== $json) {
            $request = $request->withJson($json);
        }

        $response = self::managedEdition()->send($request);

        return [$response->getStatusCode(), $response->toArray(false)];
    }
}

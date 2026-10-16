<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Runtime;

use Contao\E2eTesting\Http\HttpRequest;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;

class DefaultRuntimeTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testHandlesWebRequestsWithTheDefaultRuntime(): void
    {
        $response = self::managedEdition()->send(HttpRequest::get('/contao/login'));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
    }

    public function testPreservesTheDefaultConsoleEnvironment(): void
    {
        $output = self::managedEdition()->runConsole(['debug:container', '--parameter=kernel.environment']);

        $this->assertStringContainsString('prod', $output);
    }

    public function testDoesNotWriteRuntimeDefaultsToProjectComposerJson(): void
    {
        $composer = json_decode((string) file_get_contents(self::managedEdition()->directory().'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayNotHasKey('runtime', $composer['extra']);
    }

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        $recipe = InstallationRecipe::create(self::createMonorepoComposerConfig('core-bundle'));

        return ManagedEditionConfig::create($recipe, self::projectDirectory())->withEnvironmentVariables([
            'APP_RUNTIME' => false,
            'APP_RUNTIME_OPTIONS' => false,
        ]);
    }
}

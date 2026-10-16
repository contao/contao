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

use App\Runtime\CustomRuntime;
use Contao\E2eTesting\Http\HttpRequest;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\File\FileMapping;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;
use Symfony\Component\Filesystem\Filesystem;

class RuntimeTest extends AbstractContaoMonorepoE2ETestCase
{
    public function testRuntimeModifiesGlobalsBeforeContaoHandlesTheRequest(): void
    {
        $response = self::managedEdition()->send(HttpRequest::get('/_runtime'));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
        $this->assertSame(['secure' => true] + static::runtimeOptions(), $response->toArray());
    }

    public function testConsoleUsesTheInputProvidedByTheRuntime(): void
    {
        $output = self::managedEdition()->runConsole(['runtime:environment']);

        $this->assertStringContainsString('prod', $output);
        $this->assertStringNotContainsString('dev', $output);
    }

    public function testRuntimeOptionsAreCompiledWithoutReadingComposerJson(): void
    {
        $application = self::managedEdition();
        $composer = $application->directory().'/composer.json';
        $filesystem = new Filesystem();
        $filesystem->rename($composer, $composer.'.backup');

        try {
            $response = $application->send(HttpRequest::get('/_runtime'));

            $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
            $this->assertSame(['secure' => true] + static::runtimeOptions(), $response->toArray());
        } finally {
            $filesystem->rename($composer.'.backup', $composer);
        }
    }

    public function testPreviewResponseRemainsUncached(): void
    {
        $response = self::managedEdition()->send(HttpRequest::get('/preview.php/_runtime'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(['noindex'], $response->getHeaders(false)['x-robots-tag']);
        $this->assertStringContainsString('no-store', $response->getHeaders(false)['cache-control'][0]);
    }

    protected static function runtimeOptions(): array
    {
        return ['marker' => 'composer', 'env' => 'dev', 'debug' => true];
    }

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        $composer = self::createMonorepoComposerConfig('core-bundle')->withOverrides([
            'autoload' => ['psr-4' => ['App\\' => 'src/']],
            'extra' => ['runtime' => [
                'class' => CustomRuntime::class,
                'runtime_marker' => 'composer',
                'env' => 'dev',
                'debug' => true,
            ]],
        ]);
        $fixtures = self::projectDirectory().'/tests/E2E/Fixtures/Runtime';
        $recipe = InstallationRecipe::create($composer)
            ->withFileMapping(new FileMapping($fixtures.'/CustomRuntime.php', 'src/Runtime/CustomRuntime.php'))
            ->withFileMapping(new FileMapping($fixtures.'/RuntimeController.php', 'src/Controller/RuntimeController.php'))
        ;

        $config = ManagedEditionConfig::create($recipe, self::projectDirectory());

        return $config->withPhpServer($config->phpServer()->withIniSettings(['variables_order' => 'EGPCS']))->withEnvironmentVariables([
            'APP_RUNTIME' => false,
            'APP_RUNTIME_OPTIONS' => false,
        ]);
    }
}

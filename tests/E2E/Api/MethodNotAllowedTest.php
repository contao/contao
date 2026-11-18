<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api;

use Contao\E2eTesting\Http\HttpRequest;
use Contao\E2eTesting\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\ResponseInterface;

class MethodNotAllowedTest extends AbstractContaoMonorepoE2ETestCase
{
    #[DataProvider('provideAuthentication')]
    public function testAnswersUnsupportedMethodsWith405(bool $authenticated): void
    {
        $fixtures = self::managedEdition()->database()->fixtures();

        $response = $this->send('PUT', $fixtures->interpolate('/contao/api/dc/page/{page_main_home}'), $authenticated);
        $headers = $response->getHeaders(false);

        $this->assertSame(405, $response->getStatusCode(), $response->getContent(false));
        $this->assertSame('application/problem+json', $headers['content-type'][0] ?? null);
        $this->assertArrayHasKey('allow', $headers);
        $this->assertStringContainsString('PATCH', implode(',', $headers['allow']));
    }

    #[DataProvider('provideAuthentication')]
    public function testAnswersUnknownApiPathsWith404(bool $authenticated): void
    {
        $response = $this->send('GET', '/contao/api/does-not-exist', $authenticated);

        $this->assertSame(404, $response->getStatusCode(), $response->getContent(false));
        $this->assertSame('application/problem+json', $response->getHeaders(false)['content-type'][0] ?? null);
    }

    public static function provideAuthentication(): iterable
    {
        yield 'with token' => [true];

        yield 'without token' => [false];
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

    private function send(string $method, string $path, bool $authenticated): ResponseInterface
    {
        $headers = [
            'Accept' => 'application/ld+json',
            'Content-Type' => 'application/ld+json',
        ];

        if ($authenticated) {
            $headers['Authorization'] = 'Bearer '.self::backendAccessToken('kevin_jones');
        }

        return self::managedEdition()->send(HttpRequest::create($method, $path)->withHeaders($headers)->withBody('{}'));
    }
}

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
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FilesTest extends AbstractContaoMonorepoE2ETestCase
{
    private const array FILES = ['media/api-permissions.jpg', 'private/api-permissions.jpg'];

    #[DataProvider('provideFileMounts')]
    public function testReadsOnlyFilesInsideTheUsersMount(string $userFixture, int $allowed, int $denied): void
    {
        $files = $this->uploadFiles();

        foreach ([$files[$allowed]['iri'], $files[$allowed]['uuidIri']] as $path) {
            $this->assertSame(self::FILES[$allowed], $this->readResource($path, $userFixture)['path']);
        }

        foreach ([$files[$denied]['iri'], $files[$denied]['uuidIri']] as $path) {
            $response = self::managedEdition()->send(HttpRequest::get($path)->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken($userFixture),
                'Accept' => 'application/ld+json',
            ]));

            $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));
        }
    }

    #[DataProvider('provideFileMounts')]
    public function testWritesOnlyFilesInsideTheUsersMount(string $userFixture, int $allowed, int $denied): void
    {
        $files = $this->uploadFiles();
        $contents = file_get_contents(self::projectDirectory().'/core-bundle/tests/Fixtures/images/dummy.jpg');
        $this->assertIsString($contents);

        foreach ([
            $files[$allowed]['iri'] => 200,
            $files[$allowed]['uuidIri'] => 200,
            $files[$denied]['iri'] => 403,
            $files[$denied]['uuidIri'] => 403,
        ] as $path => $expectedStatus) {
            $response = self::managedEdition()->send(HttpRequest::create('PUT', $path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken($userFixture),
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($contents),
            );

            $this->assertSame($expectedStatus, $response->getStatusCode(), $response->getContent(false));
        }
    }

    #[DataProvider('provideUploaders')]
    public function testUploaderCannotEditMetadataInsideItsMount(string $userFixture, int $file): void
    {
        $this->uploadFiles();
        $before = $this->readResource('/contao/api/files/'.self::FILES[$file], $userFixture);

        $response = $this->updateMetadata(self::FILES[$file], $userFixture);
        $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));

        $data = $this->readResource('/contao/api/files/'.self::FILES[$file], $userFixture);
        $this->assertSame($before['metadata'], $data['metadata']);
    }

    #[DataProvider('provideFileManagers')]
    public function testFileManagerCanEditMetadataInsideItsMount(string $userFixture, int $file): void
    {
        $this->uploadFiles();

        $response = $this->updateMetadata(self::FILES[$file], $userFixture);
        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $data = $this->readResource('/contao/api/files/'.self::FILES[$file], $userFixture);
        $this->assertSame('Updated title', $data['metadata']['localized']['en']['title']);
    }

    private function updateMetadata(string $path, string $userFixture): ResponseInterface
    {
        return self::managedEdition()->send(HttpRequest::json('POST', '/contao/api/files_operations/metadata')->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken($userFixture),
            'Accept' => 'application/ld+json',
        ])->withJson([
            'path' => $path,
            'data' => ['localized' => ['en' => ['title' => 'Updated title']]],
        ]));
    }

    public static function provideFileMounts(): iterable
    {
        yield 'media uploader' => ['user_file_uploader', 0, 1];
        yield 'media manager' => ['user_media_file_manager', 0, 1];
        yield 'private uploader' => ['user_private_file_uploader', 1, 0];
        yield 'private manager' => ['user_file_manager', 1, 0];
    }

    public static function provideUploaders(): iterable
    {
        yield 'media uploader' => ['user_file_uploader', 0];
        yield 'private uploader' => ['user_private_file_uploader', 1];
    }

    public static function provideFileManagers(): iterable
    {
        yield 'media manager' => ['user_media_file_manager', 0];
        yield 'private manager' => ['user_file_manager', 1];
    }

    private function uploadFiles(): array
    {
        $contents = file_get_contents(self::projectDirectory().'/core-bundle/tests/Fixtures/images/dummy.jpg');
        $this->assertIsString($contents);
        $files = [];

        foreach (self::FILES as $path) {
            $response = self::managedEdition()->send(HttpRequest::create('PUT', '/contao/api/files/'.$path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($contents),
            );

            $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
            $file = $response->toArray(false);
            $this->assertTrue(Uuid::isValid($file['metadata']['uuid']));
            $files[] = ['iri' => $file['@id'], 'uuidIri' => '/contao/api/files/'.$file['metadata']['uuid']];
        }

        return $files;
    }

    private function readResource(string $path, string $userFixture): array
    {
        $response = self::managedEdition()->send(HttpRequest::get($path)->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken($userFixture),
            'Accept' => 'application/ld+json',
        ]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        return $response->toArray(false);
    }

    protected static function createApplicationConfig(): ManagedEditionConfig
    {
        $composer = self::createMonorepoComposerConfig('api-bundle', 'calendar-bundle', 'core-bundle', 'faq-bundle', 'news-bundle', 'newsletter-bundle');
        $recipe = InstallationRecipe::create($composer)
            ->withFixtureFile(self::projectDirectory().'/tests/E2E/Fixtures/Backend/users.yaml')
            ->withFixtureFile(self::projectDirectory().'/tests/E2E/Fixtures/Backend/default.yaml')
        ;

        return ManagedEditionConfig::create($recipe, self::projectDirectory());
    }
}

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

        $before = $this->readResource('/contao/api/files/by-path/'.self::FILES[$file], $userFixture);

        $response = $this->updateMetadata(self::FILES[$file], $userFixture);
        $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));

        $data = $this->readResource('/contao/api/files/by-path/'.self::FILES[$file], $userFixture);
        $this->assertSame($before['metadata'], $data['metadata']);
    }

    #[DataProvider('provideFileManagers')]
    public function testFileManagerCanEditMetadataInsideItsMount(string $userFixture, int $file): void
    {
        $this->uploadFiles();

        $response = $this->updateMetadata(self::FILES[$file], $userFixture);
        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $data = $this->readResource('/contao/api/files/by-path/'.self::FILES[$file], $userFixture);
        $this->assertSame('Updated title', $data['metadata']['localized']['en']['title']);
    }

    public function testKeepsUuidShapedPathsDistinctFromUuidLookups(): void
    {
        [$uuid, $literal] = $this->createUuidShapedFile();
        $byPath = '/contao/api/files/by-path/'.$uuid;
        $byUuid = '/contao/api/files/by-uuid/'.$uuid;

        $this->assertSame($uuid, $this->readResource($byPath, 'kevin_jones')['path']);
        $this->assertSame(self::FILES[0], $this->readResource($byUuid, 'kevin_jones')['path']);
        $this->assertNotSame($uuid, $literal['metadata']['uuid']);
        $this->assertSame($byPath, $literal['@id']);

        $response = $this->sendAdminRequest(HttpRequest::create('PUT', $byUuid)
            ->withHeaders(['Content-Type' => 'application/octet-stream'])->withBody('original'));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
        $this->assertSame(8, $this->readResource($byUuid, 'kevin_jones')['fileSize']);
        $this->assertSame($literal['fileSize'], $this->readResource($byPath, 'kevin_jones')['fileSize']);

        $response = $this->sendAdminRequest(HttpRequest::create('PUT', $byPath)
            ->withHeaders(['Content-Type' => 'application/octet-stream'])->withBody('literal'));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
        $this->assertSame(7, $this->readResource($byPath, 'kevin_jones')['fileSize']);
        $this->assertSame(8, $this->readResource($byUuid, 'kevin_jones')['fileSize']);
    }

    public function testUpdatesAndMovesAUuidShapedPathWithoutTargetingAnotherFile(): void
    {
        [$uuid, $literal] = $this->createUuidShapedFile();
        $operation = '/contao/api/files_operations/metadata';

        $response = $this->sendAdminRequest(HttpRequest::json('POST', $operation)->withJson([
            'path' => $uuid,
            'data' => ['localized' => ['en' => ['title' => 'Literal file']]],
        ]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $response = $this->sendAdminRequest(HttpRequest::json('POST', $operation)->withJson([
            'uuid' => $uuid,
            'data' => ['localized' => ['en' => ['title' => 'Original file']]],
        ]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
        $this->assertSame('Literal file', $this->readResource($literal['@id'], 'kevin_jones')['metadata']['localized']['en']['title']);
        $this->assertSame('Original file', $this->readResource('/contao/api/files/by-uuid/'.$uuid, 'kevin_jones')['metadata']['localized']['en']['title']);

        $destination = Uuid::v4()->toRfc4122();

        $response = $this->sendAdminRequest(HttpRequest::json('POST', '/contao/api/files_operations/move')->withHeaders(['Content-Type' => 'application/ld+json'])->withJson([
            'source' => ['path' => $uuid],
            'destination' => ['path' => $destination],
        ]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $moved = $this->readResource('/contao/api/files/by-path/'.$destination, 'kevin_jones');
        $this->assertSame($literal['metadata']['uuid'], $moved['metadata']['uuid']);
        $this->assertSame(self::FILES[0], $this->readResource('/contao/api/files/by-uuid/'.$uuid, 'kevin_jones')['path']);

        $response = $this->sendAdminRequest(HttpRequest::json('POST', '/contao/api/files_operations/move')->withHeaders(['Content-Type' => 'application/ld+json'])->withJson([
            'source' => ['uuid' => $moved['metadata']['uuid']],
            'destination' => ['path' => $uuid],
        ]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
        $this->assertSame($uuid, $response->toArray(false)['path']);
    }

    public function testListsDirectoriesWithExplicitPathAndUuidFilters(): void
    {
        $uuid = basename($this->uploadFiles()[0]['uuidIri']);
        $contents = file_get_contents(self::projectDirectory().'/core-bundle/tests/Fixtures/images/dummy.jpg');
        $this->assertIsString($contents);

        $response = $this->sendAdminRequest(HttpRequest::create('PUT', '/contao/api/files/by-path/'.$uuid.'/child.jpg')
            ->withHeaders(['Content-Type' => 'application/octet-stream'])->withBody($contents));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $directory = $this->readResource('/contao/api/files/by-path/'.$uuid, 'kevin_jones');

        foreach (['path' => $uuid, 'uuid' => $directory['metadata']['uuid']] as $type => $value) {
            $response = $this->sendAdminRequest(HttpRequest::get('/contao/api/files?'.http_build_query([$type => $value])));
            $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
            $this->assertSame($uuid.'/child.jpg', $response->toArray(false)['member'][0]['path']);
        }

        $response = $this->sendAdminRequest(HttpRequest::get('/contao/api/files?'.http_build_query(['path' => $uuid, 'uuid' => $directory['metadata']['uuid']])));
        $this->assertSame(400, $response->getStatusCode(), $response->getContent(false));
    }

    public function testDocumentsTheExplicitIdentifierRoutes(): void
    {
        $response = $this->sendAdminRequest(HttpRequest::get('/contao/api/docs.jsonopenapi'), 'application/vnd.openapi+json');
        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $paths = $response->toArray(false)['paths'];
        $this->assertArrayHasKey('/contao/api/files/by-path/{path}', $paths);
        $this->assertArrayHasKey('/contao/api/files/by-uuid/{uuid}', $paths);
    }

    private function createUuidShapedFile(): array
    {
        $uuid = basename($this->uploadFiles()[0]['uuidIri']);
        $contents = file_get_contents(self::projectDirectory().'/core-bundle/tests/Fixtures/images/dummy.jpg');
        $this->assertIsString($contents);

        $response = $this->sendAdminRequest(HttpRequest::create('PUT', '/contao/api/files/by-path/'.$uuid)
            ->withHeaders(['Content-Type' => 'application/octet-stream'])->withBody($contents));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        return [$uuid, $response->toArray(false)];
    }

    private function sendAdminRequest(HttpRequest $request, string $accept = 'application/ld+json'): ResponseInterface
    {
        return self::managedEdition()->send($request->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => $accept,
        ]));
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
            $response = self::managedEdition()->send(HttpRequest::create('PUT', '/contao/api/files/by-path/'.$path)
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

            $files[] = ['iri' => $file['@id'], 'uuidIri' => '/contao/api/files/by-uuid/'.$file['metadata']['uuid']];
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

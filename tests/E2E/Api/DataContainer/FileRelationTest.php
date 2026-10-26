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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FileRelationTest extends AbstractContaoMonorepoE2ETestCase
{
    private const array FILES = ['media/api-first.jpg', 'private/api-second.jpg'];

    public function testReadsARecordAndItsSingleRelatedFile(): void
    {
        $path = $this->createContentWithFiles('image', 'singleSRC');
        $data = $this->readResource($path);

        $this->assertSame($path, $data['@id']);
        $this->assertSame('image', $data['type']);
        $this->assertRelatedFile($data['singleSRC'], self::FILES[0]);
    }

    public function testReadsARecordAndItsMultipleRelatedFiles(): void
    {
        $path = $this->createContentWithFiles('gallery', 'multiSRC');
        $data = $this->readResource($path);

        $this->assertSame($path, $data['@id']);
        $this->assertSame('gallery', $data['type']);
        $this->assertCount(\count(self::FILES), $data['multiSRC']);

        foreach (self::FILES as $index => $expectedPath) {
            $this->assertRelatedFile($data['multiSRC'][$index], $expectedPath);
        }
    }

    public function testReadsAndUpdatesFileRelationsInsideRowWizardCells(): void
    {
        $path = $this->createContentWithFiles('text', 'file_rows');
        $data = $this->readResource($path);

        $this->assertCount(1, $data['file_rows']);

        $row = $data['file_rows'][0];

        $this->assertRelatedFile($row['single'], self::FILES[0]);
        $this->assertCount(2, $row['multiple']);
        $this->assertRelatedFile($row['multiple'][0], self::FILES[0]);
        $this->assertRelatedFile($row['multiple'][1], self::FILES[1]);

        $response = self::managedEdition()->send(
            HttpRequest::json('PATCH', $path)
                ->withHeaders([
                    'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                    'Accept' => 'application/ld+json',
                    'Content-Type' => 'application/merge-patch+json',
                ])
                ->withJson([
                    'file_rows' => [[
                        'single' => $row['multiple'][1],
                        'multiple' => [$row['multiple'][1], $row['single']],
                    ]],
                ]),
        );

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $updated = $this->readResource($path)['file_rows'][0];

        $this->assertRelatedFile($updated['single'], self::FILES[1]);
        $this->assertRelatedFile($updated['multiple'][0], self::FILES[1]);
        $this->assertRelatedFile($updated['multiple'][1], self::FILES[0]);
    }

    public function testDistinguishesUuidShapedPathsFromUuidRelations(): void
    {
        $path = $this->createContentWithFiles('text', 'file_rows');
        $file = $this->readResource($this->uploadFiles()[0]['iri']);
        $uuid = $file['metadata']['uuid'];
        $contents = file_get_contents(self::projectDirectory().'/core-bundle/tests/Fixtures/images/dummy.jpg');
        $this->assertIsString($contents);

        $response = self::managedEdition()->send(HttpRequest::create('PUT', '/contao/api/files/by-path/'.$uuid)->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
            'Content-Type' => 'application/octet-stream',
        ])->withBody($contents));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $literal = $response->toArray(false);
        $this->assertNotSame($uuid, $literal['metadata']['uuid']);

        foreach ([
            $literal['@id'] => $literal['metadata']['uuid'],
            '/contao/api/files/by-uuid/'.$uuid => $uuid,
        ] as $iri => $expectedUuid) {
            $response = $this->patchResource($path, ['file_rows' => [['single' => ['iri' => $iri], 'multiple' => []]]], 'kevin_jones');
            $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

            $reference = $this->readResource($path)['file_rows'][0]['single'];
            $this->assertSame($expectedUuid, $reference['id']);
            $this->assertSame('/contao/api/files/by-uuid/'.$expectedUuid, $reference['@id']);
        }
    }

    #[DataProvider('provideFileMounts')]
    public function testAssignsOnlySingleFileRelationsInsideTheUsersMount(string $userFixture, int $allowed, int $denied): void
    {
        $path = $this->createContentWithFiles('image', 'singleSRC');
        $files = $this->uploadFiles();

        $this->readResource($path, $userFixture);

        $response = $this->patchResource($path, ['singleSRC' => $files[$allowed]], $userFixture);
        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));
        $this->assertRelatedFile($this->readResource($path)['singleSRC'], self::FILES[$allowed]);

        $response = $this->patchResource($path, ['singleSRC' => $files[$denied]], $userFixture);
        $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));
        $this->assertRelatedFile($this->readResource($path)['singleSRC'], self::FILES[$allowed]);
    }

    #[DataProvider('provideFileMounts')]
    public function testAssignsOnlyMultipleFileRelationsInsideTheUsersMount(string $userFixture, int $allowed, int $denied): void
    {
        $path = $this->createContentWithFiles('gallery', 'multiSRC');
        $files = $this->uploadFiles();

        $this->readResource($path, $userFixture);

        $response = $this->patchResource($path, ['multiSRC' => [$files[$allowed]]], $userFixture);
        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $response = $this->patchResource($path, ['multiSRC' => [$files[$allowed], $files[$denied]]], $userFixture);
        $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));

        $references = $this->readResource($path)['multiSRC'];
        $this->assertCount(1, $references);
        $this->assertRelatedFile($references[0], self::FILES[$allowed]);
    }

    #[DataProvider('provideFileMounts')]
    public function testAssignsOnlyNestedFileRelationsInsideTheUsersMount(string $userFixture, int $allowed, int $denied): void
    {
        $path = $this->createContentWithFiles('text', 'file_rows');
        $files = $this->uploadFiles();

        $this->readResource($path, $userFixture);

        $row = ['single' => $files[$allowed], 'multiple' => [$files[$allowed]]];
        $response = $this->patchResource($path, ['file_rows' => [$row]], $userFixture);
        $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

        $row['multiple'][] = $files[$denied];
        $response = $this->patchResource($path, ['file_rows' => [$row]], $userFixture);
        $this->assertSame(403, $response->getStatusCode(), $response->getContent(false));

        $rows = $this->readResource($path)['file_rows'];
        $this->assertCount(1, $rows);
        $this->assertRelatedFile($rows[0]['single'], self::FILES[$allowed]);
        $this->assertCount(1, $rows[0]['multiple']);
        $this->assertRelatedFile($rows[0]['multiple'][0], self::FILES[$allowed]);
    }

    public static function provideFileMounts(): iterable
    {
        yield 'media content editor' => ['user_media_content_editor', 0, 1];
        yield 'private files content editor' => ['user_private_files_content_editor', 1, 0];
    }

    private function patchResource(string $path, array $data, string $userFixture): ResponseInterface
    {
        return self::managedEdition()->send(HttpRequest::json('PATCH', $path)
            ->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken($userFixture),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/merge-patch+json',
            ])
            ->withJson($data),
        );
    }

    private function assertRelatedFile(array $reference, string $path): void
    {
        $this->assertSame('/contao/api/files/by-uuid/'.$reference['id'], $reference['@id']);

        $file = $this->readResource($reference['@id']);

        $this->assertSame($path, $file['path']);
        $this->assertTrue($file['isFile']);
        $this->assertSame('image/jpeg', $file['mimeType']);
    }

    private function createContentWithFiles(string $type, string $field): string
    {
        $files = $this->uploadFiles();

        $value = match ($field) {
            'singleSRC' => $files[0],
            'multiSRC' => $files,
            'file_rows' => [['single' => $files[0], 'multiple' => $files]],
            default => throw new \InvalidArgumentException('Unknown file field: '.$field),
        };

        $path = self::managedEdition()->database()->fixtures()->interpolate('/contao/api/dc/article/{article_main_home}/content');

        $response = self::managedEdition()->send(HttpRequest::json('POST', $path)->withHeaders([
            'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
            'Accept' => 'application/ld+json',
            'Content-Type' => 'application/ld+json',
        ])->withJson([
            'type' => $type,
            $field => $value,
            ...('text' === $type ? ['text' => '<p>Related files</p>'] : []),
        ]));

        $this->assertSame(201, $response->getStatusCode(), $response->getContent(false));

        $data = $response->toArray(false);

        return $data['@id'];
    }

    private function uploadFiles(): array
    {
        $contents = file_get_contents(self::projectDirectory().'/core-bundle/tests/Fixtures/images/dummy.jpg');
        $this->assertIsString($contents);

        $files = [];

        foreach (self::FILES as $path) {
            $response = self::managedEdition()->send(HttpRequest::create('PUT', '/contao/api/files/by-path/'.$path)->withHeaders([
                'Authorization' => 'Bearer '.self::backendAccessToken('kevin_jones'),
                'Accept' => 'application/ld+json',
                'Content-Type' => 'application/octet-stream',
            ])->withBody($contents));

            $this->assertSame(200, $response->getStatusCode(), $response->getContent(false));

            $data = $response->toArray(false);

            $files[] = ['iri' => $data['@id']];
        }

        return $files;
    }

    private function readResource(string $path, string $userFixture = 'kevin_jones'): array
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

        return ManagedEditionConfig::create($recipe, self::projectDirectory())
            ->withDcaFile(self::projectDirectory().'/tests/E2E/Fixtures/FileRelations/tl_content.php')
        ;
    }
}

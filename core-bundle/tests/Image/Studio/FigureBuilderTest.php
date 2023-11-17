<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Studio;

use Contao\Config;
use Contao\CoreBundle\Event\FileMetadataEvent;
use Contao\CoreBundle\Exception\InvalidResourceException;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\LightboxResult;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DcaLoader;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeOptions;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use League\Flysystem\Config as FlysystemConfig;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\RequestContext;

class FigureBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG'], $GLOBALS['TL_MIME']);

        $this->resetStaticProperties([DcaLoader::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testFromFilesModel(): void
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = $relativeFilePath;

        $studio = $this->mockStudioForImage($absoluteFilePath);

        $this->getFigureBuilder($studio)->fromFilesModel($model)->build();
    }

    public function testFromFilesModelFailsWithInvalidDBAFSType(): void
    {
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->path = 'foo';
        $model->type = 'folder';

        $figureBuilder = $this->getFigureBuilder()->fromFilesModel($model);
        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertSame('DBAFS item "foo" is not a file.', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    public function testFromFilesModelFailsWithNonExistingResource(): void
    {
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = 'this/does/not/exist.jpg';

        $figureBuilder = $this->getFigureBuilder()->fromFilesModel($model);
        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertMatchesRegularExpression('/No resource could be located at path .*/', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    public function testFromUuid(): void
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();
        $uuid = 'foo-uuid';

        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = $relativeFilePath;

        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $filesModelAdapter
            ->method('findByUuid')
            ->with($uuid)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);
        $studio = $this->mockStudioForImage($absoluteFilePath);

        $this->getFigureBuilder($studio, $framework)->fromUuid($uuid)->build();
    }

    public function testFromUuidFailsWithNonExistingResource(): void
    {
        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $figureBuilder = $this->getFigureBuilder(null, $framework)->fromUuid('invalid-uuid');
        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertSame('DBAFS item with UUID "invalid-uuid" could not be found.', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    public function testFromId(): void
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = $relativeFilePath;

        $filesModelAdapter = $this->mockAdapter(['findByPk']);
        $filesModelAdapter
            ->method('findByPk')
            ->with(5)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);
        $studio = $this->mockStudioForImage($absoluteFilePath);

        $this->getFigureBuilder($studio, $framework)->fromId(5)->build();
    }

    public function testFromIdFailsWithNonExistingResource(): void
    {
        $filesModelAdapter = $this->mockAdapter(['findByPk']);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $figureBuilder = $this->getFigureBuilder(null, $framework)->fromId(99);
        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertSame('DBAFS item with ID "99" could not be found.', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    public function testFromAbsolutePath(): void
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = $relativeFilePath;

        $filesModelAdapter = $this->mockAdapter(['findByPath']);
        $filesModelAdapter
            ->expects($this->once())
            ->method('findByPath')
            ->with($absoluteFilePath)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);
        $studio = $this->mockStudioForImage($absoluteFilePath);

        $this->getFigureBuilder($studio, $framework)->fromPath($absoluteFilePath)->build();
    }

    public function testFromRelativePath(): void
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = $relativeFilePath;

        $filesModelAdapter = $this->mockAdapter(['findByPath']);
        $filesModelAdapter
            ->expects($this->once())
            ->method('findByPath')
            ->with($absoluteFilePath)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);
        $studio = $this->mockStudioForImage($absoluteFilePath);

        $this->getFigureBuilder($studio, $framework)->fromPath($relativeFilePath)->build();
    }

    public function testFromPathFailsWithNonExistingResource(): void
    {
        [, , $projectDir] = $this->getTestFilePaths();

        $filePath = Path::join($projectDir, 'this/does/not/exist.png');
        $figureBuilder = $this->getFigureBuilder()->fromPath($filePath, false);
        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertMatchesRegularExpression('/No resource could be located at path .*/', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    public function testFromUrl(): void
    {
        [, , , , $webDir] = $this->getTestFilePaths();

        $studio = $this->mockStudioForImage(Path::join($webDir, 'images/dummy_public.jpg'));

        $this->getFigureBuilder($studio)->fromUrl('images/d%75mmy_public.jpg')->build();
    }

    public function testFromPathAbsoluteUrl(): void
    {
        [, , , , $webDir] = $this->getTestFilePaths();

        $studio = $this->mockStudioForImage(Path::join($webDir, 'images/dummy_public.jpg'));

        $this->getFigureBuilder($studio)->fromUrl('/images/d%75mmy_public.jpg')->build();
    }

    public function testFromUrlRelativeToBaseUrl(): void
    {
        [, , , , $webDir] = $this->getTestFilePaths();

        $studio = $this->mockStudioForImage(Path::join($webDir, 'images/dummy_public.jpg'));

        $this->getFigureBuilder($studio)
            ->fromUrl(
                'https://example.com/folder/images/d%75mmy_public.jpg',
                ['https://not.example.com', 'https://example.com/folder/'],
            )
            ->build()
        ;
    }

    public function testFromUrlRelativeToRelativeBaseUrl(): void
    {
        [, , , , $webDir] = $this->getTestFilePaths();

        $studio = $this->mockStudioForImage(Path::join($webDir, 'images/dummy_public.jpg'));

        $this->getFigureBuilder($studio)
            ->fromUrl(
                'folder/subfolder/images/d%75mmy_public.jpg',
                ['folder/subfolder'],
            )
            ->build()
        ;
    }

    public function testFromUrlNotRelativeToBaseUrl(): void
    {
        $this->expectException(InvalidResourceException::class);
        $this->expectExceptionMessageMatches('/outside of base URLs/');

        $this->getFigureBuilder()
            ->fromUrl(
                'https://example.com/images/d%75mmy_public.jpg',
                ['https://not.example.com'],
            )
            ->build()
        ;
    }

    public function testFromUrlNotRelativeToNoBaseUrl(): void
    {
        $this->expectException(InvalidResourceException::class);
        $this->expectExceptionMessageMatches('/outside of base URLs/');

        $this->getFigureBuilder()
            ->fromUrl('https://example.com/images/d%75mmy_public.jpg')
            ->build()
        ;
    }

    public function testFromUrlInvalidPercentEncoding(): void
    {
        $this->expectException(InvalidResourceException::class);
        $this->expectExceptionMessageMatches('/contains invalid percent encoding/');

        $this->getFigureBuilder()
            ->fromUrl('images%2Fdummy.jpg')
            ->build()
        ;
    }

    public function testFromUrlNotRelativeToWebDir(): void
    {
        $this->expectException(InvalidResourceException::class);
        $this->expectExceptionMessageMatches('/No resource could be located at path/');

        $this->getFigureBuilder()
            ->fromUrl('images/dummy.jpg')
            ->build()
        ;
    }

    public function testFromImage(): void
    {
        [, , $projectDir] = $this->getTestFilePaths();
        $filePathOutsideUploadDir = Path::join($projectDir, 'images/dummy.jpg');

        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getPath')
            ->willReturn($filePathOutsideUploadDir)
        ;

        $studio = $this->mockStudioForImage($filePathOutsideUploadDir);

        $this->getFigureBuilder($studio)->fromImage($image)->build();
    }

    public function testFromImageFailsWithNonExistingResource(): void
    {
        $filePath = '/this/does/not/exist.png';

        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getPath')
            ->willReturn($filePath)
        ;

        $figureBuilder = $this->getFigureBuilder()->fromImage($image);
        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertMatchesRegularExpression('/No resource could be located at path .*/', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    /**
     * @dataProvider provideMixedIdentifiers
     */
    public function testFromMixed(mixed $identifier): void
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel->type = 'file';
        $filesModel->path = $relativeFilePath;

        $filesModelAdapter = $this->mockAdapter(['findByUuid', 'findByPk', 'findByPath']);
        $filesModelAdapter
            ->method('findByUuid')
            ->with('1d902bf1-2683-406e-b004-f0b59095e5a1')
            ->willReturn($filesModel)
        ;

        $filesModelAdapter
            ->method('findByPk')
            ->with(5)
            ->willReturn($filesModel)
        ;

        $filesModelAdapter
            ->method('findByUuid')
            ->with('1d902bf1-2683-406e-b004-f0b59095e5a1')
            ->willReturn($filesModel)
        ;

        $filesModelAdapter
            ->method('findByPath')
            ->with($absoluteFilePath)
            ->willReturn($filesModel)
        ;

        $validatorAdapter = $this->mockAdapter(['isUuid']);
        $validatorAdapter
            ->method('isUuid')
            ->willReturnCallback(static fn ($value): bool => '1d902bf1-2683-406e-b004-f0b59095e5a1' === $value)
        ;

        $framework = $this->mockContaoFramework([
            FilesModel::class => $filesModelAdapter,
            Validator::class => $validatorAdapter,
        ]);

        $studio = $this->mockStudioForImage($absoluteFilePath);

        $this->getFigureBuilder($studio, $framework)->from($identifier)->build();
    }

    public function testFromNullFails(): void
    {
        $figureBuilder = $this->getFigureBuilder();
        $figureBuilder->from(null);

        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertSame('The defined resource is "null".', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    public function testFromStorage(): void
    {
        $basePath = Path::canonicalize(__DIR__.'/../../Fixtures/files');
        $absoluteFilePath = Path::join($basePath, 'public/foo.jpg');

        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = 'files/public/foo.jpg';

        $filesModelAdapter = $this->mockAdapter(['findByPath']);
        $filesModelAdapter
            ->method('findByPath')
            ->with($absoluteFilePath)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);
        $studio = $this->mockStudioForImage($absoluteFilePath);

        $mountManager = new MountManager();
        $mountManager->mount(new LocalFilesystemAdapter($basePath), 'files');

        $storage = new VirtualFilesystem($mountManager, $this->createMock(DbafsManager::class), 'files');

        $this->getFigureBuilder($studio, $framework)->fromStorage($storage, 'public/foo.jpg')->build();
    }

    public function testFromStorageFailsWithUnsupportedStreamType(): void
    {
        $inMemoryAdapter = new InMemoryFilesystemAdapter();
        $inMemoryAdapter->write('foo.jpg', 'image-data', new FlysystemConfig());

        $mountManager = new MountManager();
        $mountManager->mount($inMemoryAdapter);

        $storage = new VirtualFilesystem($mountManager, $this->createMock(DbafsManager::class));

        $figureBuilder = $this->getFigureBuilder()->fromStorage($storage, 'foo.jpg');
        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertSame('Only streams of type STDIO/plainfile pointing to an absolute path are currently supported when reading an image from a storage, got "TEMP/PHP" with URI "php://temp".', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    public function testFromStorageFailsWithUnreadableResource(): void
    {
        $mountManager = new MountManager();
        $mountManager->mount(new InMemoryFilesystemAdapter());

        $storage = new VirtualFilesystem($mountManager, $this->createMock(DbafsManager::class));

        $figureBuilder = $this->getFigureBuilder()->fromStorage($storage, 'invalid/resource.jpg');
        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertSame('Could not read resource from storage: Unable to read from "invalid/resource.jpg".', $exception->getMessage());
        $this->assertNull($figureBuilder->buildIfResourceExists());

        $this->expectExceptionObject($exception);

        $figureBuilder->build();
    }

    public function provideMixedIdentifiers(): \Generator
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel->type = 'file';
        $filesModel->path = $relativeFilePath;

        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->atLeastOnce())
            ->method('getPath')
            ->willReturn($absoluteFilePath)
        ;

        yield 'files model' => [$filesModel];

        yield 'image interface' => [$image];

        yield 'uuid' => ['1d902bf1-2683-406e-b004-f0b59095e5a1'];

        yield 'id as integer' => [5];

        yield 'id as string' => ['5'];

        yield 'relative path' => [$relativeFilePath];

        yield 'absolute path' => [$absoluteFilePath];
    }

    public function testBuildIfResourceExistsHandlesFilesThatCannotBeProcessed(): void
    {
        $image = $this->createMock(ImageResult::class);
        $image
            ->method('getOriginalDimensions')
            ->willThrowException($innerException = new \Exception('Broken image'))
        ;

        [$brokenImagePath] = $this->getTestFilePaths();

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($brokenImagePath, null, null)
            ->willReturn($image)
        ;

        $figureBuilder = $this
            ->getFigureBuilder($studio)
            ->fromPath($brokenImagePath, false)
        ;

        $this->assertNull($figureBuilder->buildIfResourceExists());

        $exception = $figureBuilder->getLastException();

        $this->assertInstanceOf(InvalidResourceException::class, $exception);
        $this->assertSame('The file "'.$brokenImagePath.'" could not be opened as an image.', $exception->getMessage());
        $this->assertSame($innerException, $exception->getPrevious());
    }

    public function testLastExceptionIsResetWhenCallingFrom(): void
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = $relativeFilePath;

        $invalidModel = $this->mockClassWithProperties(FilesModel::class);
        $invalidModel->type = 'folder';

        $filesModelAdapter = $this->mockAdapter(['findByUuid', 'findByPk', 'findByPath']);
        $filesModelAdapter
            ->method('findByUuid')
            ->willReturnMap([
                ['foo-uuid', $model],
                ['invalid-uuid', null],
            ])
        ;

        $filesModelAdapter
            ->method('findByPk')
            ->willReturnMap([
                [5, $model],
                [123, null],
            ])
        ;

        $filesModelAdapter
            ->method('findByPath')
            ->willReturnMap([
                [$absoluteFilePath, $model],
                ['invalid/path', null],
            ])
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);
        $studio = $this->mockStudioForImage($absoluteFilePath);
        $figureBuilder = $this->getFigureBuilder($studio, $framework);

        $setValidResourceOperations = [
            ['fromFilesModel', $model],
            ['fromUuid', 'foo-uuid'],
            ['fromID', 5],
            ['fromPath', $absoluteFilePath],
        ];

        $setInvalidResourceOperations = [
            ['fromFilesModel', $invalidModel],
            ['fromUuid', 'invalid-uuid'],
            ['fromID', 123],
            ['fromPath', 'invalid/path'],
        ];

        $exception = null;

        foreach ($setInvalidResourceOperations as [$method, $argument]) {
            $figureBuilder->$method($argument);

            $this->assertNotSame($exception, $figureBuilder->getLastException(), 'new exception replaces old one');

            $exception = $figureBuilder->getLastException();

            $this->assertInstanceOf(InvalidResourceException::class, $exception);
        }

        foreach ($setValidResourceOperations as [$method, $argument]) {
            $figureBuilder->from($invalidModel);

            $this->assertInstanceOf(InvalidResourceException::class, $exception);

            $figureBuilder->$method($argument);

            $this->assertNull(
                $figureBuilder->getLastException(),
                'setting a valid resource clears the last exception',
            );
        }

        // Calling build must succeed if last defined resource was valid
        $figureBuilder->build();
    }

    public function testFailsWhenTryingToBuildWithoutSettingResource(): void
    {
        $figureBuilder = $this->getFigureBuilder();

        $this->expectException(\LogicException::class);

        $figureBuilder->build();
    }

    public function testSetSize(): void
    {
        [$absoluteFilePath] = $this->getTestFilePaths();

        $size = '_any_size_configuration';
        $studio = $this->mockStudioForImage($absoluteFilePath, $size);

        $this->getFigureBuilder($studio)
            ->fromPath($absoluteFilePath, false)
            ->setSize($size)
            ->build()
        ;
    }

    public function testSetResizeOptions(): void
    {
        [$absoluteFilePath] = $this->getTestFilePaths();

        $resizeOptions = new ResizeOptions();
        $studio = $this->mockStudioForImage($absoluteFilePath, null, $resizeOptions);

        $this->getFigureBuilder($studio)
            ->fromPath($absoluteFilePath, false)
            ->setResizeOptions($resizeOptions)
            ->build()
        ;
    }

    public function testSetMetadata(): void
    {
        $metadata = new Metadata(['foo' => 'bar']);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($metadata): void {
                $builder->setMetadata($metadata);
            },
        );

        $this->assertSame($metadata, $figure->getMetadata());
    }

    public function testDisableMetadata(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder
                    ->setMetadata(new Metadata(['foo' => 'bar']))
                    ->disableMetadata()
                ;
            },
        );

        $this->assertFalse($figure->hasMetadata());
    }

    /**
     * @dataProvider provideMetadataAutoFetchCases
     */
    public function testAutoFetchMetadataFromFilesModel(string $serializedMetadata, string|null $locale, array $expectedMetadata, Metadata|null $overwriteMetadata = null): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->set('router.request_context', $this->createMock(RequestContext::class));
        $container->set('contao.insert_tag.parser', new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));

        System::setContainer($container);

        $GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] = [
            'title' => '', 'alt' => '', 'link' => '', 'caption' => '',
        ];

        $currentPage = $this->mockClassWithProperties(PageModel::class);
        $currentPage->language = 'es';
        $currentPage->rootFallbackLanguage = 'de';

        $GLOBALS['objPage'] = $currentPage;

        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $filesModel = $this->mockClassWithProperties(FilesModel::class, except: ['getMetadata']);
        $filesModel->setRow([
            'type' => 'file',
            'path' => $relativeFilePath,
            'meta' => $serializedMetadata,
        ]);

        $filesModelAdapter = $this->mockAdapter(['getMetaFields']);
        $filesModelAdapter
            ->method('getMetaFields')
            ->willReturn(array_keys($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields']))
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);
        $studio = $this->mockStudioForImage($absoluteFilePath);

        $figure = $this->getFigureBuilder($studio, $framework)
            ->fromFilesModel($filesModel)
            ->setLocale($locale)
            ->setOverwriteMetadata($overwriteMetadata)
            ->build()
        ;

        $this->assertSame($expectedMetadata, $figure->getMetadata()->all());

        unset($GLOBALS['TL_DCA'], $GLOBALS['objPage']);
    }

    public function provideMetadataAutoFetchCases(): \Generator
    {
        yield 'complete metadata available in defined locale' => [
            serialize([
                'en' => ['title' => 't', 'alt' => 'a', 'link' => 'l', 'caption' => 'c'],
            ]),
            'en',
            [
                Metadata::VALUE_TITLE => 't',
                Metadata::VALUE_ALT => 'a',
                Metadata::VALUE_URL => '/l',
                Metadata::VALUE_CAPTION => 'c',
            ],
        ];

        yield '(partial) metadata available in defined locale' => [
            serialize([
                'en' => [],
                'fr' => ['title' => 'foo', 'caption' => 'bar'],
            ]),
            'fr',
            [
                Metadata::VALUE_TITLE => 'foo',
                Metadata::VALUE_ALT => '',
                Metadata::VALUE_URL => '',
                Metadata::VALUE_CAPTION => 'bar',
            ],
        ];

        yield 'no metadata available in defined locale' => [
            serialize([
                'en' => ['title' => 'foo'],
            ]),
            'de',
            [
                Metadata::VALUE_TITLE => '',
                Metadata::VALUE_ALT => '',
                Metadata::VALUE_URL => '',
                Metadata::VALUE_CAPTION => '',
            ],
        ];

        yield '(partial) metadata available in page locale' => [
            serialize([
                'es' => ['title' => 'foo'],
            ]),
            null,
            [
                Metadata::VALUE_TITLE => 'foo',
                Metadata::VALUE_ALT => '',
                Metadata::VALUE_URL => '',
                Metadata::VALUE_CAPTION => '',
            ],
        ];

        yield '(partial) metadata available in page fallback locale' => [
            serialize([
                'de' => ['title' => 'foo'],
            ]),
            null,
            [
                Metadata::VALUE_TITLE => 'foo',
                Metadata::VALUE_ALT => '',
                Metadata::VALUE_URL => '',
                Metadata::VALUE_CAPTION => '',
            ],
        ];

        yield 'no metadata available in any fallback locale' => [
            serialize([
                'en' => ['title' => 'foo'],
            ]),
            null,
            [
                Metadata::VALUE_TITLE => '',
                Metadata::VALUE_ALT => '',
                Metadata::VALUE_URL => '',
                Metadata::VALUE_CAPTION => '',
            ],
        ];

        yield 'empty metadata' => [
            '',
            null,
            [
                Metadata::VALUE_TITLE => '',
                Metadata::VALUE_ALT => '',
                Metadata::VALUE_URL => '',
                Metadata::VALUE_CAPTION => '',
            ],
        ];

        yield 'overwrite metadata keeping the other values as they are' => [
            serialize([
                'en' => ['title' => 't', 'alt' => 'a', 'link' => 'l', 'caption' => 'c'],
            ]),
            'en',
            [
                Metadata::VALUE_TITLE => 'tt',
                Metadata::VALUE_ALT => 'a',
                Metadata::VALUE_URL => '/l',
                Metadata::VALUE_CAPTION => 'c',
            ],
            new Metadata([Metadata::VALUE_TITLE => 'tt']),
        ];
    }

    public function testAutoFetchMetadataFromFilesModelFailsIfNoPage(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] = [
            'title' => '', 'alt' => '', 'link' => '', 'caption' => '',
        ];

        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel->setRow([
            'type' => 'file',
            'path' => $relativeFilePath,
            'meta' => '',
        ]);

        $filesModelAdapter = $this->mockAdapter(['getMetaFields']);
        $filesModelAdapter
            ->method('getMetaFields')
            ->willReturn(array_keys($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields']))
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);
        $studio = $this->mockStudioForImage($absoluteFilePath);
        $figure = $this->getFigureBuilder($studio, $framework)->fromFilesModel($filesModel)->build();

        $emptyMetadata = [
            Metadata::VALUE_TITLE => '',
            Metadata::VALUE_ALT => '',
            Metadata::VALUE_URL => '',
            Metadata::VALUE_CAPTION => '',
        ];

        // Note: $GLOBALS['objPage'] is not set at this point
        $this->assertSame($emptyMetadata, $figure->getMetadata()->all());
    }

    /**
     * @dataProvider provideUuidMetadataAutoFetchCases
     */
    public function testAutoSetUuidFromFilesModelWhenDefiningMetadata(FilesModel|ImageInterface|string|null $resource, Metadata|null $metadataToSet, string|null $locale, array $expectedMetadata): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] = ['title' => ''];

        $currentPage = $this->mockClassWithProperties(PageModel::class);
        $currentPage->language = 'en';
        $currentPage->rootFallbackLanguage = 'de';

        $GLOBALS['objPage'] = $currentPage;

        [$absoluteFilePath] = $this->getTestFilePaths();

        $filesModelAdapter = $this->mockAdapter(['getMetaFields', 'findByPath']);
        $filesModelAdapter
            ->method('getMetaFields')
            ->willReturn(array_keys($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields']))
        ;

        $filesModelAdapter
            ->method('findByPath')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([
            FilesModel::class => $filesModelAdapter,
            Validator::class => new Adapter(Validator::class),
        ]);

        $studio = $this->mockStudioForImage($absoluteFilePath);

        $figure = $this->getFigureBuilder($studio, $framework)
            ->from($resource)
            ->setMetadata($metadataToSet)
            ->setLocale($locale)
            ->build()
        ;

        $this->assertSame($expectedMetadata, $figure->getMetadata()->all());

        unset($GLOBALS['TL_DCA'], $GLOBALS['objPage']);
    }

    public function provideUuidMetadataAutoFetchCases(): \Generator
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $getFilesModel = function (array $metaData, string|null $uuid) use ($relativeFilePath) {
            $filesModel = $this->mockClassWithProperties(FilesModel::class, except: ['getMetadata']);
            $filesModel->setRow([
                'type' => 'file',
                'path' => $relativeFilePath,
                'meta' => serialize($metaData),
                'uuid' => $uuid,
            ]);

            return $filesModel;
        };

        yield 'explicitly set metadata without files model' => [
            $absoluteFilePath,
            new Metadata(['foo' => 'bar']),
            'de',
            ['foo' => 'bar'],
        ];

        yield 'explicitly set metadata with files model (no uuid)' => [
            $getFilesModel(['foobar' => 'baz'], null),
            new Metadata(['foo' => 'bar']),
            'de',
            ['foo' => 'bar'],
        ];

        yield 'explicitly set metadata with files model (ASCII uuid)' => [
            $getFilesModel(['foobar' => 'baz'], 'beefaff3-434a-106e-8ff0-f0b59095e5a1'),
            new Metadata(['foo' => 'bar']),
            'de',
            ['foo' => 'bar', Metadata::VALUE_UUID => 'beefaff3-434a-106e-8ff0-f0b59095e5a1'],
        ];

        yield 'explicitly set metadata with files model (binary uuid)' => [
            $getFilesModel(['foobar' => 'baz'], StringUtil::uuidToBin('beefaff3-434a-106e-8ff0-f0b59095e5a1')),
            new Metadata(['foo' => 'bar']),
            'de',
            ['foo' => 'bar', Metadata::VALUE_UUID => 'beefaff3-434a-106e-8ff0-f0b59095e5a1'],
        ];

        yield 'metadata from files model in a matching locale' => [
            $getFilesModel(['en' => ['foo' => 'bar']], 'beefaff3-434a-106e-8ff0-f0b59095e5a1'),
            null,
            'en',
            [
                Metadata::VALUE_TITLE => '',
                'foo' => 'bar',
                Metadata::VALUE_UUID => 'beefaff3-434a-106e-8ff0-f0b59095e5a1',
            ],
        ];

        yield 'default metadata from meta fields' => [
            $getFilesModel(['en' => ['foo' => 'bar']], 'beefaff3-434a-106e-8ff0-f0b59095e5a1'),
            null,
            'es',
            [
                Metadata::VALUE_TITLE => '',
                Metadata::VALUE_UUID => 'beefaff3-434a-106e-8ff0-f0b59095e5a1',
            ],
        ];
    }

    public function testSetLinkAttribute(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder->setLinkAttribute('foo', 'bar');
            },
        );

        $this->assertSame(['foo' => 'bar'], iterator_to_array($figure->getLinkAttributes()));
    }

    public function testUnsetLinkAttribute(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder->setLinkAttribute('foo', 'bar');
                $builder->setLinkAttribute('foobar', 'test');
                $builder->setLinkAttribute('foo', null);
            },
        );

        $this->assertSame(['foobar' => 'test'], iterator_to_array($figure->getLinkAttributes()));
    }

    public function testSetLinkAttributes(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder->setLinkAttributes(['foo' => 'bar', 'foobar' => 'test']);
            },
        );

        $this->assertSame(['foo' => 'bar', 'foobar' => 'test'], iterator_to_array($figure->getLinkAttributes()));
    }

    public function testSetLinkAttributesFromHtmlAttributes(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder->setLinkAttributes(new HtmlAttributes(['foo' => 'bar', 'foobar' => 'test']));
            },
        );

        $this->assertSame(['foo' => 'bar', 'foobar' => 'test'], iterator_to_array($figure->getLinkAttributes()));
    }

    /**
     * @dataProvider provideInvalidLinkAttributes
     */
    public function testSetLinkAttributesFailsWithInvalidArray(array $attributes): void
    {
        $figureBuilder = $this->getFigureBuilder();

        $this->expectException(\InvalidArgumentException::class);

        $figureBuilder->setLinkAttributes($attributes);
    }

    public function provideInvalidLinkAttributes(): \Generator
    {
        yield 'non-string keys' => [['foo', 'bar']];

        yield 'non-string values' => [['foo' => new \stdClass()]];
    }

    public function testSetLinkHref(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder->setLinkHref('https://example.com');
            },
        );

        $this->assertSame('https://example.com', $figure->getLinkHref());
    }

    public function testSetsTargetAttributeIfFullsizeWithoutLightbox(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder
                    ->setLinkHref('https://exampe.com/this-is-no-image')
                    ->enableLightbox()
                ;
            },
        );

        $this->assertSame('_blank', $figure->getLinkAttributes()['target']);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder
                    ->setLightboxResourceOrUrl('https://exampe.com/this-is-no-image')
                    ->enableLightbox()
                ;
            },
        );

        $this->assertSame('_blank', $figure->getLinkAttributes()['target']);
    }

    public function testLightboxIsDisabledByDefault(): void
    {
        $figure = $this->getFigure();

        $this->assertFalse($figure->hasLightbox());
    }

    /**
     * @dataProvider provideLightboxResourcesOrUrls
     */
    public function testSetLightboxResourceOrUrl(ImageInterface|string|null $resource, array $expectedArguments, bool $hasLightbox = true): void
    {
        if ($hasLightbox) {
            $studio = $this->mockStudioForLightbox(...$expectedArguments);
        } else {
            $studio = $this->createMock(Studio::class);
        }

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($resource): void {
                $builder
                    ->setLightboxResourceOrUrl($resource)
                    ->enableLightbox()
                ;
            },
            $studio,
        );

        $this->assertSame($hasLightbox, $figure->hasLightbox());
    }

    public function provideLightboxResourcesOrUrls(): \Generator
    {
        [$absoluteFilePath, $relativeFilePath] = $this->getTestFilePaths();

        $absoluteFilePathWithInvalidExtension = str_replace('jpg', 'xml', $absoluteFilePath);
        $relativeFilePathWithInvalidExtension = str_replace('jpg', 'xml', $relativeFilePath);

        $image = $this->createMock(ImageInterface::class);

        yield 'image interface' => [
            $image, [$image, null],
        ];

        yield 'absolute file path with valid extension' => [
            $absoluteFilePath, [$absoluteFilePath, null],
        ];

        yield 'relative file path with valid extension' => [
            $relativeFilePath, [$absoluteFilePath, null],
        ];

        yield 'absolute file path with invalid extension' => [
            $absoluteFilePathWithInvalidExtension, [null, $absoluteFilePathWithInvalidExtension], false,
        ];

        yield 'relative file path with invalid extension' => [
            $relativeFilePathWithInvalidExtension, [null, $relativeFilePathWithInvalidExtension], false,
        ];

        yield 'external url/path with valid lowercase extension' => [
            'https://example.com/valid_extension.png', [null, 'https://example.com/valid_extension.png'],
        ];

        yield 'external url/path with valid uppercase extension' => [
            'https://example.com/valid_extension.PNG', [null, 'https://example.com/valid_extension.PNG'],
        ];

        yield 'external url/path with invalid extension' => [
            'https://example.com/invalid_extension.xml', [], false,
        ];

        yield 'file path with valid extension to a non-existing resource' => [
            'this/does/not/exist.png', [], false,
        ];

        yield 'file URL with special chars to an existing resource' => [
            'files/public/foo%20%28bar%29.jpg',
            [
                Path::canonicalize(__DIR__.'/../../Fixtures/files/public/foo (bar).jpg'),
                null,
            ],
        ];

        yield 'absolute file path with special chars to an existing resource' => [
            __DIR__.'/../../Fixtures/files/public/foo (bar).jpg',
            [
                Path::canonicalize(__DIR__.'/../../Fixtures/files/public/foo (bar).jpg'),
                null,
            ],
        ];

        yield 'absolute file path with special URL chars to an non-existing resource' => [
            __DIR__.'/../../Fixtures/files/public/foo%20(bar).jpg', [], false,
        ];
    }

    /**
     * @dataProvider provideLightboxFallbackResources
     */
    public function testLightboxResourceFallback(Metadata|null $metadata, string|null $expectedFilePath, string|null $expectedUrl): void
    {
        $studio = $this->mockStudioForLightbox($expectedFilePath, $expectedUrl);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($metadata): void {
                $builder
                    ->setMetadata($metadata)
                    ->enableLightbox()
                ;
            },
            $studio,
        );

        $this->assertTrue($figure->hasLightbox());
    }

    public function provideLightboxFallbackResources(): \Generator
    {
        [$absoluteFilePath] = $this->getTestFilePaths();

        $url = 'https://example.com/valid_image.png';

        yield 'metadata with url' => [
            new Metadata([Metadata::VALUE_URL => $url]), null, $url,
        ];

        yield 'metadata without url -> use base resource' => [
            new Metadata([]), $absoluteFilePath, null,
        ];

        yield 'no metadata -> use base resource' => [
            null, $absoluteFilePath, null,
        ];
    }

    public function testSetLightboxSize(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $size = '_custom_size_configuration';
        $studio = $this->mockStudioForLightbox($image, null, $size);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($image, $size): void {
                $builder
                    ->setLightboxResourceOrUrl($image)
                    ->setLightboxSize($size)
                    ->enableLightbox()
                ;
            },
            $studio,
        );

        $this->assertTrue($figure->hasLightbox());
    }

    public function testSetLightboxResizeOptions(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $resizeOptions = new ResizeOptions();
        $studio = $this->mockStudioForLightbox($image, null, null, null, $resizeOptions);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($image, $resizeOptions): void {
                $builder
                    ->setLightboxResourceOrUrl($image)
                    ->setLightboxResizeOptions($resizeOptions)
                    ->enableLightbox()
                ;
            },
            $studio,
        );

        $this->assertTrue($figure->hasLightbox());
    }

    public function testSetLightboxGroupIdentifier(): void
    {
        $image = $this->createMock(ImageInterface::class);
        $groupIdentifier = '12345';
        $studio = $this->mockStudioForLightbox($image, null, null, $groupIdentifier);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($image, $groupIdentifier): void {
                $builder
                    ->setLightboxResourceOrUrl($image)
                    ->setLightboxGroupIdentifier($groupIdentifier)
                    ->enableLightbox()
                ;
            },
            $studio,
        );

        $this->assertTrue($figure->hasLightbox());
    }

    public function testSetTemplateOptions(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder->setOptions(['foo' => 'bar']);
            },
        );

        $this->assertSame(['foo' => 'bar'], $figure->getOptions());
    }

    public function testBuildMultipleTimes(): void
    {
        [$filePath1] = $this->getTestFilePaths();

        $filePath2 = str_replace('foo.jpg', 'bar.jpg', $filePath1);
        $metadata = new Metadata([Metadata::VALUE_ALT => 'foo']);

        $imageResult1 = $this->createMock(ImageResult::class);
        $imageResult2 = $this->createMock(ImageResult::class);
        $lightboxResource = $this->createMock(ImageInterface::class);
        $lightboxImageResult = $this->createMock(LightboxResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->exactly(2))
            ->method('createImage')
            ->willReturnMap([
                [$filePath1, null, null, $imageResult1],
                [$filePath2, null, null, $imageResult2],
            ])
        ;

        $studio
            ->expects($this->once())
            ->method('createLightboxImage')
            ->with($lightboxResource)
            ->willReturn($lightboxImageResult)
        ;

        $builder = $this->getFigureBuilder($studio);
        $builder
            ->fromPath($filePath1, false)
            ->setLinkAttribute('custom', 'foo')
            ->setMetadata($metadata)
        ;

        $figure1 = $builder->build();

        $builder
            ->fromPath($filePath2, false)
            ->setLinkAttribute('custom', 'bar')
            ->setLightboxResourceOrUrl($lightboxResource)
            ->enableLightbox()
        ;

        $figure2 = $builder->build();

        $this->assertSame($imageResult1, $figure1->getImage());
        $this->assertSame('foo', $figure1->getLinkAttributes()['custom']); // not affected by reconfiguring
        $this->assertSame($metadata, $figure1->getMetadata());
        $this->assertFalse($figure1->hasLightbox());

        $this->assertSame($imageResult2, $figure2->getImage()); // other image
        $this->assertSame('bar', $figure2->getLinkAttributes()['custom']); // other link attribute
        $this->assertSame($metadata, $figure2->getMetadata()); // same metadata
        $this->assertSame($lightboxImageResult, $figure2->getLightbox());
    }

    public function testDispatchesDefineMetadataEvent(): void
    {
        [$absoluteFilePath] = $this->getTestFilePaths();

        $studio = $this->mockStudioForImage($absoluteFilePath);

        $eventDispatcher = new EventDispatcher();

        $eventDispatcher->addListener(
            FileMetadataEvent::class,
            function (FileMetadataEvent $event): void {
                $this->assertSame([Metadata::VALUE_TITLE => 'foo'], $event->getMetadata()->all());

                $event->setMetadata(new Metadata([Metadata::VALUE_TITLE => 'bar']));
            },
        );

        $figure = $this->getFigureBuilder($studio, null, $eventDispatcher)
            ->fromPath($absoluteFilePath, false)
            ->setMetadata(new Metadata([Metadata::VALUE_TITLE => 'foo']))
            ->build()
        ;

        $this->assertSame([Metadata::VALUE_TITLE => 'bar'], $figure->getMetadata()->all());
    }

    private function getFigure(\Closure|null $configureBuilderCallback = null, Studio|null $studio = null): Figure
    {
        [$absoluteFilePath] = $this->getTestFilePaths();

        $studio ??= $this->mockStudioForImage($absoluteFilePath);
        $builder = $this->getFigureBuilder($studio)->fromPath($absoluteFilePath, false);

        if ($configureBuilderCallback) {
            $configureBuilderCallback($builder);
        }

        return $builder->build();
    }

    private function mockStudioForImage(string $expectedFilePath, string|null $expectedSizeConfiguration = null, ResizeOptions|null $resizeOptions = null): Studio&MockObject
    {
        $image = $this->createMock(ImageResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($expectedFilePath, $expectedSizeConfiguration, $resizeOptions)
            ->willReturn($image)
        ;

        return $studio;
    }

    private function mockStudioForLightbox(ImageInterface|string|null $expectedResource, string|null $expectedUrl, string|null $expectedSizeConfiguration = null, string|null $expectedGroupIdentifier = null, ResizeOptions|null $resizeOptions = null): Studio&MockObject
    {
        $lightbox = $this->createMock(LightboxResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createLightboxImage')
            ->with($expectedResource, $expectedUrl, $expectedSizeConfiguration, $expectedGroupIdentifier, $resizeOptions)
            ->willReturn($lightbox)
        ;

        return $studio;
    }

    private function getFigureBuilder(Studio|null $studio = null, ContaoFramework|null $framework = null, EventDispatcher|null $eventDispatcher = null): FigureBuilder
    {
        [, , $projectDir, $uploadPath, $webDir] = $this->getTestFilePaths();
        $validExtensions = $this->getTestFileExtensions();

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->method('get')
            ->willReturnMap([
                ['contao.image.studio', $studio],
                ['contao.framework', $framework],
                ['event_dispatcher', $eventDispatcher ?? new EventDispatcher()],
            ])
        ;

        return new FigureBuilder($locator, $projectDir, $uploadPath, $webDir, $validExtensions);
    }

    private function getTestFilePaths(): array
    {
        $projectDir = Path::canonicalize(__DIR__.'/../../Fixtures');
        $uploadPath = 'files';
        $relativeFilePath = Path::join($uploadPath, 'public/foo.jpg');
        $absoluteFilePath = Path::join($projectDir, $relativeFilePath);
        $webDir = Path::join($projectDir, 'public');

        return [$absoluteFilePath, $relativeFilePath, $projectDir, $uploadPath, $webDir];
    }

    private function getTestFileExtensions(): array
    {
        return ['jpg', 'png'];
    }
}

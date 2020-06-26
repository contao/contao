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

use Contao\CoreBundle\Exception\InvalidResourceException;
use Contao\CoreBundle\File\MetaData;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\LightBoxResult;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\PageModel;
use Contao\System;
use Contao\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Webmozart\PathUtil\Path;

class FigureBuilderTest extends TestCase
{
    public function testFromFilesModel(): void
    {
        $filePath = __FILE__;
        $projectDir = __DIR__;

        /** @var FileSModel&MockObject $model */
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = Path::getFilename($filePath);

        $studio = $this->getStudioMockForImage($filePath);

        $this->getFigureBuilder($studio, null, $projectDir)
            ->fromFilesModel($model)
            ->build()
        ;
    }

    public function testFromFilesModelFailsWithInvalidDBAFSType(): void
    {
        /** @var FileSModel&MockObject $model */
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'folder';

        $this->expectException(InvalidResourceException::class);

        $this->getFigureBuilder()->fromFilesModel($model);
    }

    public function testFromFilesModelFailsWithNonExistingResource(): void
    {
        /** @var FileSModel&MockObject $model */
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'path';
        $model->path = 'this/does/not/exist.jpg';

        $this->expectException(InvalidResourceException::class);

        $this->getFigureBuilder()->fromFilesModel($model);
    }

    public function testFromUuid(): void
    {
        $filePath = __FILE__;
        $projectDir = __DIR__;
        $uuid = 'foo-uuid';

        /** @var FileSModel&MockObject $model */
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = Path::getFilename($filePath);

        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $filesModelAdapter
            ->method('findByUuid')
            ->with($uuid)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $studio = $this->getStudioMockForImage($filePath);

        $this->getFigureBuilder($studio, $framework, $projectDir)
            ->fromUuid($uuid)
            ->build()
        ;
    }

    public function testFromUuidFailsWithNonExistingResource(): void
    {
        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $this->expectException(InvalidResourceException::class);

        $this->getFigureBuilder(null, $framework)->fromUuid('invalid-uuid');
    }

    public function testFromId(): void
    {
        $filePath = __FILE__;
        $projectDir = __DIR__;
        $id = 5;

        /** @var FileSModel&MockObject $model */
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = Path::getFilename($filePath);

        $filesModelAdapter = $this->mockAdapter(['findByPk']);
        $filesModelAdapter
            ->method('findByPk')
            ->with($id)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $studio = $this->getStudioMockForImage($filePath);

        $this->getFigureBuilder($studio, $framework, $projectDir)
            ->fromId($id)
            ->build()
        ;
    }

    public function testFromIdFailsWithNonExistingResource(): void
    {
        $filesModelAdapter = $this->mockAdapter(['findByPk']);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $this->expectException(InvalidResourceException::class);

        $this->getFigureBuilder(null, $framework)->fromId(99);
    }

    public function testFromAbsolutePath(): void
    {
        $projectDir = \dirname(__DIR__);
        $uploadPath = Path::makeRelative(__DIR__, $projectDir);
        $absoluteFilePath = __FILE__;
        $relativeFilePath = Path::makeRelative($absoluteFilePath, $projectDir);

        /** @var FileSModel&MockObject $model */
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = $relativeFilePath;

        $filesModelAdapter = $this->mockAdapter(['findByPath']);
        $filesModelAdapter
            ->method('findByPath')
            ->with($absoluteFilePath)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $studio = $this->getStudioMockForImage($absoluteFilePath);

        $this->getFigureBuilder($studio, $framework, $projectDir, $uploadPath)
            ->fromPath($absoluteFilePath)
            ->build()
        ;
    }

    public function testFromRelativePath(): void
    {
        $projectDir = \dirname(__DIR__);
        $uploadPath = Path::makeRelative(__DIR__, $projectDir);
        $absoluteFilePath = __FILE__;
        $relativeFilePath = Path::makeRelative($absoluteFilePath, $projectDir);

        /** @var FileSModel&MockObject $model */
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = $relativeFilePath;

        $filesModelAdapter = $this->mockAdapter(['findByPath']);
        $filesModelAdapter
            ->method('findByPath')
            ->with($absoluteFilePath)
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $studio = $this->getStudioMockForImage($absoluteFilePath);

        $this->getFigureBuilder($studio, $framework, $projectDir, $uploadPath)
            ->fromPath($relativeFilePath)
            ->build()
        ;
    }

    public function testFromPathFailsWithNonExistingResource(): void
    {
        $projectDir = \dirname(__DIR__);
        $filePath = Path::join($projectDir, 'this/does/not/exist.png');

        $this->expectException(InvalidResourceException::class);

        $this->getFigureBuilder(null, null, $projectDir)
            ->fromPath($filePath, false)
        ;
    }

    public function testFromImage(): void
    {
        $filePath = __FILE__;

        /** @var ImageInterface&MockObject $image */
        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getPath')
            ->willReturn($filePath)
        ;

        $studio = $this->getStudioMockForImage($filePath);

        $this->getFigureBuilder($studio, null, '/path/to/project', 'files')
            ->fromImage($image)
            ->build()
        ;
    }

    public function testFromImageFailsWithNonExistingResource(): void
    {
        $filePath = 'this/does/not/exist.png';

        /** @var ImageInterface&MockObject $image */
        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getPath')
            ->willReturn($filePath)
        ;

        $this->expectException(InvalidResourceException::class);

        $this->getFigureBuilder(null, null, '/path/to/project', 'files')
            ->fromImage($image)
        ;
    }

    /**
     * @dataProvider provideMixedIdentifiers
     */
    public function testFromMixed($identifier): void
    {
        $projectDir = \dirname(__DIR__);
        $uploadPath = Path::makeRelative(__DIR__, $projectDir);
        $absoluteFilePath = __FILE__;
        $relativeFilePath = Path::makeRelative($absoluteFilePath, $projectDir);

        /** @var FileSModel&MockObject $filesModel */
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
            ->willReturnCallback(
                static function ($value): bool {
                    return '1d902bf1-2683-406e-b004-f0b59095e5a1' === $value;
                }
            )
        ;

        $framework = $this->mockContaoFramework([
            FilesModel::class => $filesModelAdapter,
            Validator::class => $validatorAdapter,
        ]);

        $studio = $this->getStudioMockForImage(__FILE__);

        $this->getFigureBuilder($studio, $framework, $projectDir, $uploadPath)
            ->from($identifier)
            ->build()
        ;
    }

    public function provideMixedIdentifiers(): \Generator
    {
        $absoluteFilePath = __FILE__;
        $relativeFilePath = Path::makeRelative($absoluteFilePath, \dirname(__DIR__));

        /** @var FileSModel&MockObject $filesModel */
        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel->type = 'file';
        $filesModel->path = $relativeFilePath;

        /** @var ImageInterface&MockObject $image */
        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getPath')
            ->willReturn($absoluteFilePath)
        ;

        yield 'files model' => [$filesModel];

        yield 'image interface' => [$image];

        yield 'uuid' => ['1d902bf1-2683-406e-b004-f0b59095e5a1'];

        yield 'id' => [5];

        yield 'relative path' => [$relativeFilePath];

        yield 'absolute path' => [$absoluteFilePath];
    }

    public function testFailsWhenTryingToBuildWithoutSettingResource(): void
    {
        $this->expectException(\LogicException::class);

        $this->getFigureBuilder()->build();
    }

    public function testSetSize(): void
    {
        $size = '_any_size_configuration';

        $studio = $this->getStudioMockForImage(__FILE__, $size);

        $this->getFigureBuilder($studio, null, '/path/to/project', 'files')
            ->fromPath(__FILE__, false)
            ->setSize($size)
            ->build()
        ;
    }

    public function testSetMetaData(): void
    {
        $metaData = new MetaData(['foo' => 'bar']);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($metaData): void {
                $builder->setMetaData($metaData);
            }
        );

        $this->assertSame($metaData, $figure->getMetaData());
    }

    public function testDisableMetaData(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder
                    ->setMetaData(new MetaData(['foo' => 'bar']))
                    ->disableMetaData()
                ;
            }
        );

        $this->assertFalse($figure->hasMetaData());
    }

    /**
     * @dataProvider provideMetaDataAutoFetchCases
     */
    public function testAutoFetchMetaDataFromFilesModel(string $serializedMetaData, $locale, array $expectedMetaData): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] = [
            'title' => '', 'alt' => '', 'link' => '', 'caption' => '',
        ];

        /** @var PageModel&MockObject $currentPage */
        $currentPage = $this->mockClassWithProperties(PageModel::class);
        $currentPage->language = 'es';
        $currentPage->rootFallbackLanguage = 'de';

        $GLOBALS['objPage'] = $currentPage;

        $filePath = __FILE__;
        $projectDir = __DIR__;

        /** @var FilesModel $filesModel */
        $filesModel = (new \ReflectionClass(FilesModel::class))
            ->newInstanceWithoutConstructor()
        ;

        $filesModel->setRow([
            'type' => 'file',
            'path' => $filePath,
            'meta' => $serializedMetaData,
        ]);

        $filesModelAdapter = $this->mockAdapter(['getMetaFields']);
        $filesModelAdapter
            ->method('getMetaFields')
            ->willReturn(array_keys($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields']))
        ;

        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $studio = $this->getStudioMockForImage($filePath);

        $figure = $this->getFigureBuilder($studio, $framework, $projectDir)
            ->fromFilesModel($filesModel)
            ->setLocale($locale)
            ->build()
        ;

        $this->assertSame($expectedMetaData, $figure->getMetaData()->all());
    }

    public function provideMetaDataAutoFetchCases(): \Generator
    {
        yield 'complete meta data available in defined locale' => [
            serialize([
                'en' => ['title' => 't', 'alt' => 'a', 'link' => 'l', 'caption' => 'c'],
            ]),
            'en',
            [
                MetaData::VALUE_TITLE => 't',
                MetaData::VALUE_ALT => 'a',
                MetaData::VALUE_URL => 'l',
                MetaData::VALUE_CAPTION => 'c',
            ],
        ];

        yield '(partial) meta data available in defined locale' => [
            serialize([
                'en' => [],
                'fr' => ['title' => 'foo', 'caption' => 'bar'],
            ]),
            'fr',
            [
                MetaData::VALUE_TITLE => 'foo',
                MetaData::VALUE_ALT => '',
                MetaData::VALUE_URL => '',
                MetaData::VALUE_CAPTION => 'bar',
            ],
        ];

        yield 'no meta data available in defined locale' => [
            serialize([
                'en' => ['title' => 'foo'],
            ]),
            'de',
            [
                MetaData::VALUE_TITLE => '',
                MetaData::VALUE_ALT => '',
                MetaData::VALUE_URL => '',
                MetaData::VALUE_CAPTION => '',
            ],
        ];

        yield '(partial) meta data available in page locale' => [
            serialize([
                'es' => ['title' => 'foo'],
            ]),
            null,
            [
                MetaData::VALUE_TITLE => 'foo',
                MetaData::VALUE_ALT => '',
                MetaData::VALUE_URL => '',
                MetaData::VALUE_CAPTION => '',
            ],
        ];

        yield '(partial) meta data available in page fallback locale' => [
            serialize([
                'de' => ['title' => 'foo'],
            ]),
            null,
            [
                MetaData::VALUE_TITLE => 'foo',
                MetaData::VALUE_ALT => '',
                MetaData::VALUE_URL => '',
                MetaData::VALUE_CAPTION => '',
            ],
        ];

        yield 'no meta data available in any fallback locale' => [
            serialize([
                'en' => ['title' => 'foo'],
            ]),
            null,
            [
                MetaData::VALUE_TITLE => '',
                MetaData::VALUE_ALT => '',
                MetaData::VALUE_URL => '',
                MetaData::VALUE_CAPTION => '',
            ],
        ];

        yield 'empty meta data' => [
            '',
            null,
            [
                MetaData::VALUE_TITLE => '',
                MetaData::VALUE_ALT => '',
                MetaData::VALUE_URL => '',
                MetaData::VALUE_CAPTION => '',
            ],
        ];
    }

    public function testSetLinkAttribute(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder->setLinkAttribute('foo', 'bar');
            }
        );

        $this->assertSame(['foo' => 'bar'], $figure->getLinkAttributes());
    }

    public function testSetLinkHref(): void
    {
        $figure = $this->getFigure(
            static function (FigureBuilder $builder): void {
                $builder->setLinkAttribute('href', 'https://example.com');
            }
        );

        $this->assertSame('https://example.com', $figure->getLinkHref());
    }

    public function testLightBoxIsDisabledByDefault(): void
    {
        $figure = $this->getFigure();

        $this->assertFalse($figure->hasLightBox());
    }

    /**
     * @dataProvider provideLightBoxResourcesOrUrls
     */
    public function testSetLightBoxResourceOrUrl($resource, array $expectedArguments, bool $hasLightBox = true): void
    {
        $projectDir = \dirname(__DIR__);
        $uploadPath = Path::makeRelative(__DIR__, $projectDir);
        $validExtensions = ['php', 'png'];

        if ($hasLightBox) {
            $studio = $this->getStudioMockForLightBox(...$expectedArguments);
        } else {
            /** @var Studio&MockObject $studio */
            $studio = $this->createMock(Studio::class);
        }

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($resource): void {
                $builder
                    ->setLightBoxResourceOrUrl($resource)
                    ->enableLightBox()
                ;
            },
            $studio,
            $projectDir,
            $uploadPath,
            $validExtensions
        );

        $this->assertSame($hasLightBox, $figure->hasLightBox());
    }

    public function provideLightBoxResourcesOrUrls(): \Generator
    {
        $absoluteFilePath = __FILE__;
        $relativeFilePath = Path::makeRelative($absoluteFilePath, \dirname(__DIR__));

        $absoluteFilePathWithInvalidExtension = str_replace('php', 'xml', $absoluteFilePath);
        $relativeFilePathWithInvalidExtension = str_replace('php', 'xml', $relativeFilePath);

        /** @var ImageInterface&MockObject $image */
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
            $absoluteFilePathWithInvalidExtension, [null, $absoluteFilePathWithInvalidExtension],
        ];

        yield 'relative file path with invalid extension' => [
            $relativeFilePathWithInvalidExtension, [null, $relativeFilePathWithInvalidExtension],
        ];

        yield 'external url/path with valid extension' => [
            'https://example.com/valid_extension.png', [null, 'https://example.com/valid_extension.png'],
        ];

        yield 'external url/path with invalid extension' => [
            'https://example.com/invalid_extension.xml', [], false,
        ];

        yield 'file path with valid extension to a non-existing resource' => [
            'this/does/not/exist.png', [], false,
        ];
    }

    /**
     * @dataProvider provideLightBoxFallbackResources
     */
    public function testLightBoxResourceFallback(?MetaData $metaData, ?string $expectedFilePath, ?string $expectedUrl): void
    {
        $projectDir = \dirname(__DIR__);
        $uploadPath = Path::makeRelative(__DIR__, $projectDir);
        $validExtensions = ['php', 'png'];

        $studio = $this->getStudioMockForLightBox($expectedFilePath, $expectedUrl);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($metaData): void {
                $builder
                    ->setMetaData($metaData)
                    ->enableLightBox()
                ;
            },
            $studio,
            $projectDir,
            $uploadPath,
            $validExtensions
        );

        $this->assertTrue($figure->hasLightBox());
    }

    public function provideLightBoxFallbackResources(): \Generator
    {
        $absoluteFilePath = __FILE__;
        $url = 'https://example.com/valid_image.png';

        yield 'meta data with url' => [
            new MetaData([MetaData::VALUE_URL => $url]), null, $url,
        ];

        yield 'meta data without url -> use base resource' => [
            new MetaData([]), $absoluteFilePath, null,
        ];

        yield 'no meta data -> use base resource' => [
            null, $absoluteFilePath, null,
        ];
    }

    public function testSetLightBoxSize(): void
    {
        /** @var ImageInterface&MockObject $image */
        $image = $this->createMock(ImageInterface::class);

        $size = '_custom_size_configuration';

        $studio = $this->getStudioMockForLightBox($image, null, $size);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($image, $size): void {
                $builder
                    ->setLightBoxResourceOrUrl($image)
                    ->setLightBoxSize($size)
                    ->enableLightBox()
                ;
            },
            $studio
        );

        $this->assertTrue($figure->hasLightBox());
    }

    public function testSetLightBoxGroupIdentifier(): void
    {
        /** @var ImageInterface&MockObject $image */
        $image = $this->createMock(ImageInterface::class);

        $groupIdentifier = '12345';

        $studio = $this->getStudioMockForLightBox($image, null, null, $groupIdentifier);

        $figure = $this->getFigure(
            static function (FigureBuilder $builder) use ($image, $groupIdentifier): void {
                $builder
                    ->setLightBoxResourceOrUrl($image)
                    ->setLightBoxGroupIdentifier($groupIdentifier)
                    ->enableLightBox()
                ;
            },
            $studio
        );

        $this->assertTrue($figure->hasLightBox());
    }

    public function testBuildMultipleTimes(): void
    {
        $filePath1 = __FILE__;
        $filePath2 = __DIR__.'/FigureTest.php';

        $metaData = new MetaData([MetaData::VALUE_ALT => 'foo']);

        /** @var ImageResult&MockObject $imageResult1 */
        $imageResult1 = $this->createMock(ImageResult::class);

        /** @var ImageResult&MockObject $imageResult2 */
        $imageResult2 = $this->createMock(ImageResult::class);

        /** @var ImageInterface&MockObject $lightBoxResource */
        $lightBoxResource = $this->createMock(ImageInterface::class);

        /** @var LightBoxResult&MockObject $lightBoxImageResult */
        $lightBoxImageResult = $this->createMock(LightBoxResult::class);

        /** @var Studio&MockObject $studio */
        $studio = $this->createMock(Studio::class);

        $studio
            ->expects($this->exactly(2))
            ->method('createImage')
            ->willReturnMap([
                [$filePath1, null, $imageResult1],
                [$filePath2, null, $imageResult2],
            ])
        ;

        $studio
            ->expects($this->once())
            ->method('createLightBoxImage')
            ->with($lightBoxResource)
            ->willReturn($lightBoxImageResult)
        ;

        $builder = $this->getFigureBuilder(
            $studio, null, '/path/to/project'
        );

        $builder
            ->fromPath($filePath1, false)
            ->setLinkAttribute('custom', 'foo')
            ->setMetaData($metaData)
        ;

        $figure1 = $builder->build();

        $builder
            ->fromPath($filePath2, false)
            ->setLinkAttribute('custom', 'bar')
            ->setLightBoxResourceOrUrl($lightBoxResource)
            ->enableLightBox()
        ;

        $figure2 = $builder->build();

        $this->assertSame($imageResult1, $figure1->getImage());
        $this->assertSame('foo', $figure1->getLinkAttributes()['custom']); // not affected by reconfiguring
        $this->assertSame($metaData, $figure1->getMetaData());
        $this->assertFalse($figure1->hasLightBox());

        $this->assertSame($imageResult2, $figure2->getImage()); // other image
        $this->assertSame('bar', $figure2->getLinkAttributes()['custom']); // other link attribute
        $this->assertSame($metaData, $figure2->getMetaData()); // same meta data
        $this->assertSame($lightBoxImageResult, $figure2->getLightBox());
    }

    private function getFigure(\Closure $configureBuilderCallback = null, Studio $studio = null, string $projectDir = '/path/to/project', string $uploadPath = 'files', array $validExtensions = []): Figure
    {
        if (null === $studio) {
            $studio = $this->getStudioMockForImage(__FILE__);
        }

        $builder = $this->getFigureBuilder($studio, null, $projectDir, $uploadPath, $validExtensions)
            ->fromPath(__FILE__, false)
        ;

        if (null !== $configureBuilderCallback) {
            $configureBuilderCallback($builder);
        }

        return $builder->build();
    }

    /**
     * @return MockObject&Studio
     */
    private function getStudioMockForImage(string $expectedFilePath, $expectedSizeConfiguration = null)
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);

        /** @var Studio&MockObject $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($expectedFilePath, $expectedSizeConfiguration)
            ->willReturn($image)
        ;

        return $studio;
    }

    /**
     * @return MockObject&Studio
     */
    private function getStudioMockForLightBox($expectedResource, ?string $expectedUrl, $expectedSizeConfiguration = null, string $expectedGroupIdentifier = null)
    {
        /** @var LightBoxResult&MockObject $lightBox */
        $lightBox = $this->createMock(LightBoxResult::class);

        /** @var ContainerInterface&MockObject $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createLightBoxImage')
            ->with($expectedResource, $expectedUrl, $expectedSizeConfiguration, $expectedGroupIdentifier)
            ->willReturn($lightBox)
        ;

        return $studio;
    }

    private function getFigureBuilder(Studio $studio = null, ContaoFramework $framework = null, string $projectDir = null, string $uploadPath = null, array $validExtensions = []): FigureBuilder
    {
        /** @var ContainerInterface&MockObject $locator */
        $locator = $this->createMock(ContainerInterface::class);

        $parameterBag = null;

        if (null !== $projectDir || null !== $uploadPath || null !== $validExtensions) {
            $parameterBag = $this->createMock(ParameterBagInterface::class);
            $parameterBag
                ->method('get')
                ->willReturnMap([
                    ['kernel.project_dir', $projectDir],
                    ['contao.upload_path', $uploadPath],
                    ['contao.image.valid_extensions', $validExtensions],
                ])
            ;
        }

        $locator
            ->method('get')
            ->willReturnMap([
                ['parameter_bag', $parameterBag],
                [Studio::class, $studio],
                ['contao.framework', $framework],
            ])
        ;

        return new FigureBuilder($locator);
    }
}

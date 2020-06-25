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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
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

        $studio = $this->getStudioMock($filePath);

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

        $studio = $this->getStudioMock($filePath);

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

        $studio = $this->getStudioMock($filePath);

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

        $studio = $this->getStudioMock($absoluteFilePath);

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

        $studio = $this->getStudioMock($absoluteFilePath);

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

        $studio = $this->getStudioMock($filePath);

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

        $studio = $this->getStudioMock(__FILE__);

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

    public function testSetSize(): void
    {
        $size = '_any_size_configuration';

        $studio = $this->getStudioMock(__FILE__, $size);

        $this->getFigureBuilder($studio, null, '/path/to/project', 'files')
            ->fromPath(__FILE__, false)
            ->setSize($size)
            ->build()
        ;
    }

//    public function testSetMetaData(): void
//    {
//    }
//
//    public function testDisableMetaData(): void
//    {
//    }
//
//    public function testSetLocale(): void
//    {
//    }
//
//    public function testSetLinkAttribute(): void
//    {
//    }
//
//    public function testSetLinkHref(): void
//    {
//    }
//
//    public function testSetLightBoxResourceOrUrl(): void
//    {
//    }
//
//    public function testSetLightBoxSize(): void
//    {
//    }
//
//    public function testSetLightBoxGroupIdentifier(): void
//    {
//    }
//
//    public function testEnableLightBox(): void
//    {
//    }
//
//    public function testBuildMultipleTimes(): void
//    {
//    }

    /**
     * @return MockObject&Studio
     */
    private function getStudioMock(string $expectedFilePath, $expectedSizeConfiguration = null)
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);

        /** @var ContainerInterface&MockObject $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($expectedFilePath, $expectedSizeConfiguration)
            ->willReturn($image)
        ;

        return $studio;
    }

    private function getFigureBuilder(Studio $studio = null, ContaoFramework $framework = null, string $projectDir = null, string $uploadPath = null): FigureBuilder
    {
        /** @var ContainerInterface&MockObject $locator */
        $locator = $this->createMock(ContainerInterface::class);

        $parameterBag = null;

        if (null !== $projectDir || null !== $uploadPath) {
            $parameterBag = $this->createMock(ParameterBagInterface::class);
            $parameterBag
                ->expects($this->atLeastOnce())
                ->method('get')
                ->willReturnMap([
                    ['kernel.project_dir', $projectDir],
                    ['contao.upload_path', $uploadPath],
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

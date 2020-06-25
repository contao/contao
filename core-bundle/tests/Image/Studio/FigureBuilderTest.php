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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Webmozart\PathUtil\Path;

class FigureBuilderTest extends TestCase
{
    public function testFromFilesModel(): void
    {
        $filePath = __FILE__;  // use this file, so that `file_exists()` is true
        $projectDir = __DIR__;

        /** @var FileSModel&MockObject $model */
        $model = $this->mockClassWithProperties(FilesModel::class);
        $model->type = 'file';
        $model->path = Path::getFilename($filePath);

        $studio = $this->getStudioMock($filePath, null); // expect absolute path

        $this->getFigureBuilder($projectDir, $studio)
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
        $filePath = __FILE__;  // use this file, so that `file_exists()` is true
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

        $studio = $this->getStudioMock($filePath, null); // expect absolute path

        $this->getFigureBuilder($projectDir, $studio, $framework)
            ->fromUuid($uuid)
            ->build()
        ;
    }

    public function testFromUuidFailsWithNonExistingResource(): void
    {
        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $this->expectException(InvalidResourceException::class);

        $this->getFigureBuilder(null, null, $framework)->fromUuid('invalid-uuid');
    }

    public function testFromId(): void
    {
        $filePath = __FILE__;  // use this file, so that `file_exists()` is true
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

        $studio = $this->getStudioMock($filePath, null); // expect absolute path

        $this->getFigureBuilder($projectDir, $studio, $framework)
            ->fromId($id)
            ->build()
        ;
    }

    public function testFromIdFailsWithNonExistingResource(): void
    {
        $filesModelAdapter = $this->mockAdapter(['findByPk']);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesModelAdapter]);

        $this->expectException(InvalidResourceException::class);

        $this->getFigureBuilder(null, null, $framework)->fromId(99);
    }

    /**
     * @return MockObject&Studio
     */
    private function getStudioMock(string $expectedFilePath, $expectedSizeConfiguration)
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

    private function getFigureBuilder(string $projectDir = null, Studio $studio = null, ContaoFramework $framework = null): FigureBuilder
    {
        /** @var ContainerInterface&MockObject $locator */
        $locator = $this->createMock(ContainerInterface::class);

        $parameterBag = null;

        if (null !== $projectDir) {
            $parameterBag = $this->createMock(ParameterBagInterface::class);
            $parameterBag
                ->expects($this->atLeastOnce())
                ->method('get')
                ->with('kernel.project_dir')
                ->willReturn($projectDir)
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

//    public function testFromPath(): void
//    {
//
//    }
//
//    public function testFromImage(): void
//    {
//
//    }
//
//    public function testFromMixed(): void
//    {
//
//    }
//
//    public function testSetSize(): void
//    {
//
//    }
//
//    public function testSetMetaData(): void
//    {
//
//    }
//
//    public function testDisableMetaData(): void
//    {
//
//    }
//
//    public function testSetLocale(): void
//    {
//
//    }
//
//    public function testSetLinkAttribute(): void
//    {
//
//    }
//
//    public function testSetLinkHref(): void
//    {
//
//    }
//
//    public function testSetLightBoxResourceOrUrl(): void
//    {
//
//    }
//
//    public function testSetLightBoxSize(): void
//    {
//
//    }
//
//    public function testSetLightBoxGroupIdentifier(): void
//    {
//
//    }
//
//    public function testEnableLightBox(): void
//    {
//
//    }
//
//    public function testBuildMultipleTimes(): void
//    {
//
//    }
}

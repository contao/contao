<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Contao\Files;
use Contao\FilesModel;
use Contao\Image as ContaoImage;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizer;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Contao\Image\ImportantPart;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\Image\ResizerInterface;
use Contao\ImageSizeModel;
use Contao\System;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\ImagineInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ImageFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $filesystem = new Filesystem();

        foreach (['assets', 'images'] as $directory) {
            $filesystem->mirror(
                Path::join((new self())->getFixturesDir(), $directory),
                Path::join(self::getTempDir(), $directory)
            );
        }

        System::setContainer($this->getContainerWithContaoConfiguration(self::getTempDir()));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove(Path::join($this->getTempDir(), 'assets/images'));

        $this->resetStaticProperties([System::class, File::class, Files::class]);

        parent::tearDown();
    }

    public function testCreatesAnImageObjectFromAnImagePath(): void
    {
        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');
        $imageMock = $this->createMock(ImageInterface::class);

        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->expects($this->exactly(2))
            ->method('resize')
            ->with(
                $this->callback(
                    function (Image $image) use (&$path): bool {
                        $this->assertSame($path, $image->getPath());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config): bool {
                        $this->assertSame(100, $config->getWidth());
                        $this->assertSame(200, $config->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_BOX, $config->getMode());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertFalse($options->getSkipIfDimensionsMatch());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $filesModel = $this->mockClassWithProperties(FilesModel::class);

        $filesAdapter = $this->mockConfiguredAdapter(['findByPath' => $filesModel]);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesAdapter]);
        $imageFactory = $this->getImageFactory($resizer, null, null, null, $framework);
        $image = $imageFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSameImage($imageMock, $image);

        $path = Path::join($this->getTempDir(), 'assets/images/dummy.jpg');

        (new Filesystem())->dumpFile($path, '');

        $image = $imageFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSameImage($imageMock, $image);
    }

    public function testCreatesAnImageObjectFromAnImagePathWithEmptySize(): void
    {
        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');
        $imageMock = $this->createMock(ImageInterface::class);

        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->expects($this->exactly(2))
            ->method('resize')
            ->with(
                $this->callback(
                    function (Image $image) use (&$path): bool {
                        $this->assertSame($path, $image->getPath());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config): bool {
                        $this->assertTrue($config->isEmpty());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertTrue($options->getSkipIfDimensionsMatch());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesAdapter = $this->mockConfiguredAdapter(['findByPath' => $filesModel]);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesAdapter]);
        $imageFactory = $this->getImageFactory($resizer, null, null, null, $framework);
        $image = $imageFactory->create($path, ['', '', '']);

        $this->assertSameImage($imageMock, $image);

        $image = $imageFactory->create($path, [0, 0, 'box']);

        $this->assertSameImage($imageMock, $image);
    }

    public function testFailsToCreateAnImageObjectIfTheFileExtensionIsInvalid(): void
    {
        $imageFactory = $this->getImageFactory();

        $this->expectException('InvalidArgumentException');

        $imageFactory->create(Path::join($this->getTempDir(), 'images/dummy.foo'));
    }

    public function testFailsToCreateAnImageObjectIfThePathIsNotAbsolute(): void
    {
        $imageFactory = $this->getImageFactory();

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Image path "images/dummy.jpg" must be absolute');

        $imageFactory->create('images/dummy.jpg');
    }

    public function testCreatesAnImageObjectFromAnImagePathWithAnImageSize(): void
    {
        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');
        $imageMock = $this->createMock(ImageInterface::class);

        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(
                    function (Image $image) use ($path): bool {
                        $this->assertSame($path, $image->getPath());

                        $this->assertSameImportantPart(
                            new ImportantPart(0.5, 0.5, 0.25, 0.25),
                            $image->getImportantPart()
                        );

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config): bool {
                        $this->assertSame(100, $config->getWidth());
                        $this->assertSame(200, $config->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_BOX, $config->getMode());
                        $this->assertSame(50, $config->getZoomLevel());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertSame([
                            'jpeg_quality' => 80,
                            'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                        ], $options->getImagineOptions());

                        $this->assertSame(Path::join($this->getTempDir(), 'target/path.jpg'), $options->getTargetPath());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $imageSizeProperties = [
            'width' => 100,
            'height' => 200,
            'resizeMode' => ResizeConfiguration::MODE_BOX,
            'zoom' => 50,
        ];

        $imageSizeModel = $this->mockClassWithProperties(ImageSizeModel::class, $imageSizeProperties);
        $imageSizeModel
            ->method('row')
            ->willReturn($imageSizeProperties)
        ;

        $imageSizeAdapter = $this->mockConfiguredAdapter(['findByPk' => $imageSizeModel]);

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel->importantPartX = 0.5;
        $filesModel->importantPartY = 0.5;
        $filesModel->importantPartWidth = 0.25;
        $filesModel->importantPartHeight = 0.25;

        $filesAdapter = $this->mockConfiguredAdapter(['findByPath' => $filesModel]);

        $adapters = [
            ImageSizeModel::class => $imageSizeAdapter,
            FilesModel::class => $filesAdapter,
        ];

        $framework = $this->mockContaoFramework($adapters);
        $imageFactory = $this->getImageFactory($resizer, null, null, null, $framework);
        $image = $imageFactory->create($path, 1, Path::join($this->getTempDir(), 'target/path.jpg'));

        $this->assertSame($imageMock, $image);
    }

    public function testCreatesAnImageObjectFromAnImagePathIfTheImageSizeIsMissing(): void
    {
        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');
        $imageSizeAdapter = $this->mockConfiguredAdapter(['findByPk' => null]);
        $filesAdapter = $this->mockConfiguredAdapter(['findByPath' => null]);

        $adapters = [
            ImageSizeModel::class => $imageSizeAdapter,
            FilesModel::class => $filesAdapter,
        ];

        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->method('resize')
            ->willReturnCallback(
                function (ImageInterface $image, ResizeConfiguration $config) {
                    $this->assertTrue($config->isEmpty());

                    return $image;
                }
            )
        ;

        $framework = $this->mockContaoFramework($adapters);
        $imageFactory = $this->getImageFactory($resizer, null, null, null, $framework);
        $image = $imageFactory->create($path, 1);

        $this->assertSame($path, $image->getPath());
    }

    public function testCreatesAnImageObjectFromAnImageObjectWithAPredefinedImageSize(): void
    {
        $predefinedSizes = [
            'foobar' => [
                'width' => 100,
                'height' => 200,
                'resizeMode' => ResizeConfiguration::MODE_BOX,
                'zoom' => 50,
            ],
        ];

        $imageMock = $this->createMock(ImageInterface::class);

        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock): bool {
                        $this->assertSameImage($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config) use ($predefinedSizes): bool {
                        $this->assertSame($predefinedSizes['foobar']['width'], $config->getWidth());
                        $this->assertSame($predefinedSizes['foobar']['height'], $config->getHeight());
                        $this->assertSame($predefinedSizes['foobar']['resizeMode'], $config->getMode());
                        $this->assertSame($predefinedSizes['foobar']['zoom'], $config->getZoomLevel());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertSame(
                            [
                                'jpeg_quality' => 80,
                                'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                            ],
                            $options->getImagineOptions()
                        );

                        $this->assertSame(Path::join($this->getTempDir(), 'target/path.jpg'), $options->getTargetPath());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $imageFactory = $this->getImageFactory($resizer);
        $imageFactory->setPredefinedSizes($predefinedSizes);

        $image = $imageFactory->create($imageMock, [null, null, 'foobar'], Path::join($this->getTempDir(), 'target/path.jpg'));

        $this->assertSameImage($imageMock, $image);
    }

    public function testCreatesAnImageObjectFromAnImageObjectWithAResizeConfiguration(): void
    {
        $resizeConfig = (new ResizeConfiguration())
            ->setWidth(100)
            ->setHeight(200)
            ->setMode(ResizeConfiguration::MODE_BOX)
            ->setZoomLevel(50)
        ;

        $imageMock = $this->createMock(ImageInterface::class);

        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock): bool {
                        $this->assertSameImage($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config) use ($resizeConfig): bool {
                        $this->assertSame($resizeConfig->isEmpty(), $config->isEmpty());
                        $this->assertSame($resizeConfig->getWidth(), $config->getWidth());
                        $this->assertSame($resizeConfig->getHeight(), $config->getHeight());
                        $this->assertSame($resizeConfig->getMode(), $config->getMode());
                        $this->assertSame($resizeConfig->getZoomLevel(), $config->getZoomLevel());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertSame([
                            'jpeg_quality' => 80,
                            'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                        ], $options->getImagineOptions());

                        $this->assertSame(Path::join($this->getTempDir(), 'target/path.jpg'), $options->getTargetPath());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $imageFactory = $this->getImageFactory($resizer);
        $image = $imageFactory->create($imageMock, $resizeConfig, Path::join($this->getTempDir(), 'target/path.jpg'));

        $this->assertSameImage($imageMock, $image);
    }

    public function testCreatesAnImageObjectFromAnImageObjectWithAnEmptyResizeConfiguration(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageFactory = $this->getImageFactory();
        $image = $imageFactory->create($imageMock, new ResizeConfiguration());

        $this->assertSameImage($imageMock, $image);
    }

    public function testCreatesADeferredImageObjectFromAnImagePath(): void
    {
        $path = Path::join($this->getTempDir(), 'images/non-existent-deferred.jpg');
        $imageMock = $this->createMock(DeferredImageInterface::class);

        $resizer = $this->createMock(DeferredResizer::class);
        $resizer
            ->method('getDeferredImage')
            ->with($path)
            ->willReturn($imageMock)
        ;

        $imageFactory = $this->getImageFactory($resizer);
        $image = $imageFactory->create($path);

        $this->assertSame($imageMock, $image);
    }

    /**
     * @dataProvider getCreateWithLegacyMode
     */
    public function testCreatesAnImageObjectFromAnImagePathInLegacyMode(string $mode, array $expected): void
    {
        $path = Path::join($this->getTempDir(), 'images/none.jpg');
        $imageMock = $this->createMock(ImageInterface::class);

        $filesystem = $this
            ->getMockBuilder(Filesystem::class)
            ->onlyMethods(['exists'])
            ->getMock()
        ;

        $filesystem
            ->expects($this->exactly(2))
            ->method('exists')
            ->willReturn(true)
        ;

        $resizer = $this->createMock(ResizerInterface::class);
        $resizer
            ->expects($this->exactly(2))
            ->method('resize')
            ->with(
                $this->callback(
                    function (Image $image) use ($path, $expected): bool {
                        $this->assertSame($path, $image->getPath());

                        $this->assertSameImportantPart(
                            new ImportantPart(
                                $expected[0],
                                $expected[1],
                                $expected[2],
                                $expected[3]
                            ),
                            $image->getImportantPart()
                        );

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config): bool {
                        $this->assertSame(50, $config->getWidth());
                        $this->assertSame(50, $config->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_CROP, $config->getMode());
                        $this->assertSame(0, $config->getZoomLevel());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $imagine = $this->createMock(ImagineInterface::class);
        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesAdapter = $this->mockConfiguredAdapter(['findByPath' => $filesModel]);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesAdapter]);
        $imageFactory = $this->getImageFactory($resizer, $imagine, $imagine, $filesystem, $framework);
        $image = $imageFactory->create($path, [50, 50, $mode]);
        $imageFromSerializedConfig = $imageFactory->create($path, serialize([50, 50, $mode]));

        $this->assertSame($imageMock, $image);
        $this->assertSame($imageMock, $imageFromSerializedConfig);
    }

    /**
     * @group legacy
     * @dataProvider getInvalidImportantParts
     */
    public function testCreatesAnImageObjectFromAnImagePathWithInvalidImportantPart(array $invalid, array $expected): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.8: Defining the important part in absolute pixels has been deprecated %s.');

        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel->importantPartX = $invalid[0];
        $filesModel->importantPartY = $invalid[1];
        $filesModel->importantPartWidth = $invalid[2];
        $filesModel->importantPartHeight = $invalid[3];

        $filesAdapter = $this->mockConfiguredAdapter(['findByPath' => $filesModel]);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesAdapter]);
        $imageFactory = $this->getImageFactory(null, null, null, null, $framework);
        $image = $imageFactory->create($path);

        $this->assertSameImportantPart(
            new ImportantPart($expected[0], $expected[1], $expected[2], $expected[3]),
            $image->getImportantPart()
        );

        $this->assertSame($path, $image->getPath());
    }

    public function getInvalidImportantParts(): \Generator
    {
        yield [[20, 20, 160, 160], [0.1, 0.1, 0.8, 0.8]];
        yield [[0, 0, 100, 100], [0, 0, 0.5, 0.5]];
        yield [[0, 0, 200, 200], [0, 0, 1, 1]];
        yield [[1, 1, 1, 1], [0.005, 0.005, 0.005, 0.005]];
        yield [[0, 0, 999, 100], [0, 0, 1, 1]];
    }

    /**
     * @dataProvider getCreateWithLegacyMode
     */
    public function testReturnsTheImportantPartFromALegacyMode(string $mode, array $expected): void
    {
        $dimensionsMock = $this->createMock(ImageDimensions::class);
        $dimensionsMock
            ->method('getSize')
            ->willReturn(new Box(100, 100))
        ;

        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock
            ->method('getDimensions')
            ->willReturn($dimensionsMock)
        ;

        $imageFactory = $this->getImageFactory();

        $this->assertSameImportantPart(
            new ImportantPart(
                $expected[0],
                $expected[1],
                $expected[2],
                $expected[3]
            ),
            $imageFactory->getImportantPartFromLegacyMode($imageMock, $mode)
        );
    }

    public function getCreateWithLegacyMode(): \Generator
    {
        yield 'Left Top' => ['left_top', [0, 0, 0, 0]];
        yield 'Left Center' => ['left_center', [0, 0, 0, 1]];
        yield 'Left Bottom' => ['left_bottom', [0, 1, 0, 0]];
        yield 'Center Top' => ['center_top', [0, 0, 1, 0]];
        yield 'Center Center' => ['center_center', [0, 0, 1, 1]];
        yield 'Center Bottom' => ['center_bottom', [0, 1, 1, 0]];
        yield 'Right Top' => ['right_top', [1, 0, 0, 0]];
        yield 'Right Center' => ['right_center', [1, 0, 0, 1]];
        yield 'Right Bottom' => ['right_bottom', [1, 1, 0, 0]];
        yield 'Invalid' => ['top_left', [0, 0, 1, 1]];
    }

    public function testFailsToReturnTheImportantPartIfTheModeIsInvalid(): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageFactory = $this->getImageFactory();

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('not a legacy resize mode');

        $imageFactory->getImportantPartFromLegacyMode($imageMock, 'invalid');
    }

    public function testCreatesAnImageObjectFromAnImagePathWithoutAResizer(): void
    {
        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');
        $adapter = $this->mockConfiguredAdapter(['findByPath' => null]);
        $framework = $this->mockContaoFramework([FilesModel::class => $adapter]);

        $imageFactory = $this->getImageFactory(null, null, null, null, $framework);
        $image = $imageFactory->create($path);

        $this->assertSame($path, $image->getPath());
    }

    /**
     * @group legacy
     */
    public function testExecutesTheExecuteResizeHook(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpg';

        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');
        $adapter = $this->mockConfiguredAdapter(['findByPath' => null]);
        $framework = $this->mockContaoFramework([FilesModel::class => $adapter]);

        $resizer = new LegacyResizer(Path::join($this->getTempDir(), 'assets/images'), new ResizeCalculator());
        $resizer->setFramework($framework);

        $imagine = new Imagine();
        $imageFactory = $this->getImageFactory($resizer, $imagine, $imagine, null, $framework);

        System::getContainer()->set('contao.image.factory', $imageFactory);

        $GLOBALS['TL_HOOKS'] = [
            'executeResize' => [[static::class, 'executeResizeHookCallback']],
        ];

        $image = $imageFactory->create($path, [100, 100, ResizeConfiguration::MODE_CROP]);

        $this->assertSame(
            Path::canonicalize(Path::join($this->getTempDir(), 'assets/images/dummy.jpg&executeResize_100_100_crop__Contao-Image.jpg')),
            Path::canonicalize($image->getPath())
        );

        $image = $imageFactory->create($path, [200, 200, ResizeConfiguration::MODE_CROP]);

        $this->assertSame(
            Path::canonicalize(Path::join($this->getTempDir(), 'assets/images/dummy.jpg&executeResize_200_200_crop__Contao-Image.jpg')),
            Path::canonicalize($image->getPath())
        );

        $image = $imageFactory->create(
            $path,
            [200, 200, ResizeConfiguration::MODE_CROP],
            Path::join($this->getTempDir(), 'target.jpg')
        );

        $this->assertSame(
            Path::canonicalize(Path::join($this->getTempDir(), 'assets/images/dummy.jpg&executeResize_200_200_crop_target.jpg_Contao-Image.jpg')),
            Path::canonicalize($image->getPath())
        );

        unset($GLOBALS['TL_CONFIG']['validImageTypes'], $GLOBALS['TL_HOOKS']);
    }

    public static function executeResizeHookCallback(ContaoImage $imageObj): string
    {
        // Do not include $cacheName as it is dynamic (mtime)
        $relativePath = 'assets/'
            .$imageObj->getOriginalPath()
            .'&executeResize'
            .'_'.$imageObj->getTargetWidth()
            .'_'.$imageObj->getTargetHeight()
            .'_'.$imageObj->getResizeMode()
            .'_'.$imageObj->getTargetPath()
            .'_'.str_replace('\\', '-', \get_class($imageObj))
            .'.jpg';

        $filesystem = new Filesystem();
        $path = Path::join(System::getContainer()->getParameter('kernel.project_dir'), $relativePath);

        if (!$filesystem->exists($dir = Path::getDirectory($path))) {
            $filesystem->mkdir($dir);
        }

        $filesystem->dumpFile($path, '');

        return $relativePath;
    }

    /**
     * @group legacy
     */
    public function testExecutesTheGetImageHook(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpg';

        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');
        $adapter = $this->mockConfiguredAdapter(['findByPath' => null]);
        $framework = $this->mockContaoFramework([FilesModel::class => $adapter]);

        $resizer = new LegacyResizer(Path::join($this->getTempDir(), 'assets/images'), new ResizeCalculator());
        $resizer->setFramework($framework);

        $imagine = new Imagine();
        $imageFactory = $this->getImageFactory($resizer, $imagine, $imagine, null, $framework);

        System::getContainer()->set('contao.image.factory', $imageFactory);

        $GLOBALS['TL_HOOKS'] = [
            'executeResize' => [[static::class, 'executeResizeHookCallback']],
        ];

        // Build cache before adding the hook
        $imageFactory->create($path, [50, 50, ResizeConfiguration::MODE_CROP]);

        $GLOBALS['TL_HOOKS'] = [
            'getImage' => [[static::class, 'getImageHookCallback']],
        ];

        $image = $imageFactory->create($path, [100, 100, ResizeConfiguration::MODE_CROP]);

        $this->assertSame(
            Path::canonicalize(Path::join($this->getTempDir(), 'assets/images/dummy.jpg&getImage_100_100_crop_Contao-File__Contao-Image.jpg')),
            Path::canonicalize($image->getPath())
        );

        $image = $imageFactory->create($path, [50, 50, ResizeConfiguration::MODE_CROP]);

        $this->assertMatchesRegularExpression(
            '(/images/.*dummy.*.jpg$)',
            $image->getPath(),
            'Hook should not get called for cached images'
        );

        $image = $imageFactory->create(
            $path,
            (new ResizeConfiguration())->setWidth(200)->setHeight(200),
            (new ResizeOptions())->setSkipIfDimensionsMatch(true)
        );

        $this->assertSame(
            Path::normalize($this->getTempDir()).'/images/dummy.jpg',
            Path::normalize($image->getPath()),
            'Hook should not get called if no resize is necessary'
        );

        unset($GLOBALS['TL_CONFIG']['validImageTypes'], $GLOBALS['TL_HOOKS']);
    }

    public static function getImageHookCallback(string $originalPath, int $targetWidth, int $targetHeight, string $resizeMode, string $cacheName, File $fileObj, string $targetPath, ContaoImage $imageObj): string
    {
        // Do not include $cacheName as it is dynamic (mtime)
        $relativePath = 'assets/'
            .$originalPath
            .'&getImage'
            .'_'.$targetWidth
            .'_'.$targetHeight
            .'_'.$resizeMode
            .'_'.str_replace('\\', '-', \get_class($fileObj))
            .'_'.$targetPath
            .'_'.str_replace('\\', '-', \get_class($imageObj))
            .'.jpg';

        $filesystem = new Filesystem();
        $path = Path::join(System::getContainer()->getParameter('kernel.project_dir'), $relativePath);

        if (!$filesystem->exists($dir = Path::getDirectory($path))) {
            $filesystem->mkdir($dir);
        }

        $filesystem->dumpFile($path, '');

        return $relativePath;
    }

    /**
     * @group legacy
     */
    public function testIgnoresAnEmptyHookReturnValue(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpg';

        $path = Path::join($this->getTempDir(), 'images/dummy.jpg');

        $adapters = [
            FilesModel::class => $this->mockConfiguredAdapter(['findByPath' => null]),
            Config::class => $this->mockConfiguredAdapter(['get' => 3000]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $resizer = new LegacyResizer(Path::join($this->getTempDir(), 'assets/images'), new ResizeCalculator());
        $resizer->setFramework($framework);

        $imagine = new Imagine();
        $imageFactory = $this->getImageFactory($resizer, $imagine, $imagine, null, $framework);

        System::getContainer()->set('contao.image.factory', $imageFactory);

        $GLOBALS['TL_HOOKS'] = [
            'getImage' => [[static::class, 'emptyHookCallback']],
        ];

        $image = $imageFactory->create($path, [100, 100, ResizeConfiguration::MODE_CROP]);

        $this->assertMatchesRegularExpression('(/images/.*dummy.*.jpg$)', $image->getPath(), 'Empty hook should be ignored');
        $this->assertSame(100, $image->getDimensions()->getSize()->getWidth());
        $this->assertSame(100, $image->getDimensions()->getSize()->getHeight());

        unset($GLOBALS['TL_CONFIG']['validImageTypes'], $GLOBALS['TL_HOOKS']);
    }

    public static function emptyHookCallback(): void
    {
    }

    private function getImageFactory(ResizerInterface $resizer = null, ImagineInterface $imagine = null, ImagineInterface $imagineSvg = null, Filesystem $filesystem = null, ContaoFramework $framework = null, bool $bypassCache = null, array $imagineOptions = null, array $validExtensions = null, string $uploadDir = null): ImageFactory
    {
        $resizer ??= $this->createMock(ResizerInterface::class);
        $imagine ??= $this->createMock(ImagineInterface::class);
        $imagineSvg ??= $this->createMock(ImagineInterface::class);
        $filesystem ??= new Filesystem();
        $framework ??= $this->createMock(ContaoFramework::class);
        $bypassCache ??= false;
        $validExtensions ??= ['jpg', 'svg'];

        // Do not use Path::join here (see #4596)
        $uploadDir ??= $this->getTempDir().'/images';

        if (null === $imagineOptions) {
            $imagineOptions = [
                'jpeg_quality' => 80,
                'interlace' => ImagineImageInterface::INTERLACE_PLANE,
            ];
        }

        return new ImageFactory(
            $resizer,
            $imagine,
            $imagineSvg,
            $filesystem,
            $framework,
            $bypassCache,
            $imagineOptions,
            $validExtensions,
            $uploadDir
        );
    }

    private function assertSameImage(ImageInterface $imageA, ImageInterface $imageB): void
    {
        $this->assertSameDimensions($imageA->getDimensions(), $imageB->getDimensions());
        $this->assertSameImportantPart($imageA->getImportantPart(), $imageB->getImportantPart());
        $this->assertSame($imageA->getPath(), $imageB->getPath());
        $this->assertSame($imageA->getUrl($this->getFixturesDir()), $imageB->getUrl($this->getFixturesDir()));
    }

    private function assertSameImportantPart(ImportantPart $partA, ImportantPart $partB): void
    {
        $this->assertSame($partA->getX(), $partB->getX());
        $this->assertSame($partA->getY(), $partB->getY());
        $this->assertSame($partA->getHeight(), $partB->getHeight());
        $this->assertSame($partA->getWidth(), $partB->getWidth());
    }

    private function assertSameDimensions(ImageDimensions $dimensionsA, ImageDimensions $dimensionsB): void
    {
        $this->assertSame($dimensionsA->getSize()->getHeight(), $dimensionsB->getSize()->getHeight());
        $this->assertSame($dimensionsA->getSize()->getWidth(), $dimensionsB->getSize()->getWidth());
        $this->assertSame($dimensionsA->isRelative(), $dimensionsB->isRelative());
        $this->assertSame($dimensionsA->isUndefined(), $dimensionsB->isUndefined());
    }
}

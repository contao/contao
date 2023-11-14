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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Contao\Files;
use Contao\FilesModel;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizer;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Contao\Image\ImportantPart;
use Contao\Image\Metadata\ExifFormat;
use Contao\Image\Metadata\IptcFormat;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\Image\ResizerInterface;
use Contao\ImageSizeModel;
use Contao\System;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\ImagineInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ImageFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $filesystem = new Filesystem();

        foreach (['assets', 'images'] as $directory) {
            $filesystem->mirror(
                Path::join((new self())->getFixturesDir(), $directory),
                Path::join(self::getTempDir(), $directory),
            );
        }

        System::setContainer($this->getContainerWithContaoConfiguration(self::getTempDir()));
    }

    #[\Override]
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
                    },
                ),
                $this->callback(
                    function (ResizeConfiguration $config): bool {
                        $this->assertSame(100, $config->getWidth());
                        $this->assertSame(200, $config->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_BOX, $config->getMode());

                        return true;
                    },
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertFalse($options->getSkipIfDimensionsMatch());

                        return true;
                    },
                ),
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
                    },
                ),
                $this->callback(
                    function (ResizeConfiguration $config): bool {
                        $this->assertTrue($config->isEmpty());

                        return true;
                    },
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertTrue($options->getSkipIfDimensionsMatch());

                        return true;
                    },
                ),
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
                            $image->getImportantPart(),
                        );

                        return true;
                    },
                ),
                $this->callback(
                    function (ResizeConfiguration $config): bool {
                        $this->assertSame(100, $config->getWidth());
                        $this->assertSame(200, $config->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_BOX, $config->getMode());
                        $this->assertSame(50, $config->getZoomLevel());

                        return true;
                    },
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertSame(
                            [
                                'jpeg_quality' => 77,
                                'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                                'quality' => 77,
                                'webp_quality' => 77,
                                'avif_quality' => 77,
                                'heic_quality' => 77,
                                'jxl_quality' => 77,
                            ],
                            $options->getImagineOptions(),
                        );

                        $this->assertSame(
                            [
                                ExifFormat::NAME => ExifFormat::DEFAULT_PRESERVE_KEYS,
                                IptcFormat::NAME => IptcFormat::DEFAULT_PRESERVE_KEYS,
                            ],
                            $options->getPreserveCopyrightMetadata(),
                        );

                        $this->assertSame(Path::join($this->getTempDir(), 'target/path.jpg'), $options->getTargetPath());

                        return true;
                    },
                ),
            )
            ->willReturn($imageMock)
        ;

        $imageSizeProperties = [
            'width' => 100,
            'height' => 200,
            'resizeMode' => ResizeConfiguration::MODE_BOX,
            'zoom' => 50,
            'imageQuality' => 77,
            'preserveMetadata' => 'overwrite',
            'preserveMetadataFields' => serialize([
                serialize([ExifFormat::NAME => ExifFormat::DEFAULT_PRESERVE_KEYS]),
                serialize([IptcFormat::NAME => IptcFormat::DEFAULT_PRESERVE_KEYS]),
            ]),
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
                },
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
                'imagineOptions' => [
                    'jpeg_quality' => 77,
                    'jxl_quality' => 66,
                ],
                'preserveMetadataFields' => [
                    ExifFormat::NAME => [],
                    IptcFormat::NAME => ['2#116', '2#080'],
                ],
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
                    },
                ),
                $this->callback(
                    function (ResizeConfiguration $config) use ($predefinedSizes): bool {
                        $this->assertSame($predefinedSizes['foobar']['width'], $config->getWidth());
                        $this->assertSame($predefinedSizes['foobar']['height'], $config->getHeight());
                        $this->assertSame($predefinedSizes['foobar']['resizeMode'], $config->getMode());
                        $this->assertSame($predefinedSizes['foobar']['zoom'], $config->getZoomLevel());

                        return true;
                    },
                ),
                $this->callback(
                    function (ResizeOptions $options) use ($predefinedSizes): bool {
                        $this->assertSame(
                            [
                                'jpeg_quality' => 77,
                                'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                                'jxl_quality' => 66,
                            ],
                            $options->getImagineOptions(),
                        );

                        $this->assertSame(
                            $predefinedSizes['foobar']['preserveMetadataFields'][ExifFormat::NAME],
                            $options->getPreserveCopyrightMetadata()[ExifFormat::NAME],
                        );

                        $this->assertSame(
                            $predefinedSizes['foobar']['preserveMetadataFields'][IptcFormat::NAME],
                            $options->getPreserveCopyrightMetadata()[IptcFormat::NAME],
                        );

                        $this->assertSame(Path::join($this->getTempDir(), 'target/path.jpg'), $options->getTargetPath());

                        return true;
                    },
                ),
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
                    },
                ),
                $this->callback(
                    function (ResizeConfiguration $config) use ($resizeConfig): bool {
                        $this->assertSame($resizeConfig->isEmpty(), $config->isEmpty());
                        $this->assertSame($resizeConfig->getWidth(), $config->getWidth());
                        $this->assertSame($resizeConfig->getHeight(), $config->getHeight());
                        $this->assertSame($resizeConfig->getMode(), $config->getMode());
                        $this->assertSame($resizeConfig->getZoomLevel(), $config->getZoomLevel());

                        return true;
                    },
                ),
                $this->callback(
                    function (ResizeOptions $options): bool {
                        $this->assertSame([
                            'jpeg_quality' => 80,
                            'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                        ], $options->getImagineOptions());

                        $this->assertSame(Path::join($this->getTempDir(), 'target/path.jpg'), $options->getTargetPath());

                        return true;
                    },
                ),
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
     *
     * @group legacy
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
                                $expected[3],
                            ),
                            $image->getImportantPart(),
                        );

                        return true;
                    },
                ),
                $this->callback(
                    function (ResizeConfiguration $config): bool {
                        $this->assertSame(50, $config->getWidth());
                        $this->assertSame(50, $config->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_CROP, $config->getMode());
                        $this->assertSame(0, $config->getZoomLevel());

                        return true;
                    },
                ),
            )
            ->willReturn($imageMock)
        ;

        $imagine = $this->createMock(ImagineInterface::class);
        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesAdapter = $this->mockConfiguredAdapter(['findByPath' => $filesModel]);
        $framework = $this->mockContaoFramework([FilesModel::class => $filesAdapter]);
        $imageFactory = $this->getImageFactory($resizer, $imagine, $imagine, $filesystem, $framework);

        $this->expectDeprecation("%slegacy resize mode \"$mode\" has been deprecated%s");

        $image = $imageFactory->create($path, [50, 50, $mode]);
        $imageFromSerializedConfig = $imageFactory->create($path, serialize([50, 50, $mode]));

        $this->assertSame($imageMock, $image);
        $this->assertSame($imageMock, $imageFromSerializedConfig);
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
                $expected[3],
            ),
            $imageFactory->getImportantPartFromLegacyMode($imageMock, $mode),
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

    private function getImageFactory(ResizerInterface|null $resizer = null, ImagineInterface|null $imagine = null, ImagineInterface|null $imagineSvg = null, Filesystem|null $filesystem = null, ContaoFramework|null $framework = null, bool|null $bypassCache = null, array|null $imagineOptions = null, array|null $validExtensions = null, string|null $uploadDir = null): ImageFactory
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
            $uploadDir,
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

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
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\ImageInterface;
use Contao\Image\Picture;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\PictureGeneratorInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;
use Contao\Model\Collection;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class PictureFactoryTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testCreatesAPictureObjectFromAnImagePath(): void
    {
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock): bool {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $pictureConfig): bool {
                        $size = $pictureConfig->getSize();

                        $this->assertSame(100, $size->getResizeConfig()->getWidth());
                        $this->assertSame(200, $size->getResizeConfig()->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_BOX, $size->getResizeConfig()->getMode());
                        $this->assertSame(50, $size->getResizeConfig()->getZoomLevel());
                        $this->assertSame('1x, 2x', $size->getDensities());
                        $this->assertSame('100vw', $size->getSizes());

                        /** @var PictureConfigurationItem $sizeItem */
                        $sizeItem = $pictureConfig->getSizeItems()[0];

                        $this->assertSame(50, $sizeItem->getResizeConfig()->getWidth());
                        $this->assertSame(50, $sizeItem->getResizeConfig()->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_CROP, $sizeItem->getResizeConfig()->getMode());
                        $this->assertSame(100, $sizeItem->getResizeConfig()->getZoomLevel());
                        $this->assertSame('0.5x, 2x', $sizeItem->getDensities());
                        $this->assertSame('50vw', $sizeItem->getSizes());
                        $this->assertSame('(max-width: 900px)', $sizeItem->getMedia());

                        $this->assertSame(['webp', 'gif'], $pictureConfig->getFormats()['gif']);
                        $this->assertSame(['webp', 'png', 'jpg'], $pictureConfig->getFormats()['webp']);

                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->callback(
                    function (string $imagePath) use ($path): bool {
                        $this->assertSame($path, $imagePath);

                        return true;
                    }
                ),
                $this->callback(
                    function (?ResizeConfiguration $size): bool {
                        $this->assertNull($size);

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
            'sizes' => '100vw',
            'densities' => '1x, 2x',
            'cssClass' => 'my-size',
            'lazyLoading' => true,
            'formats' => serialize(['gif:webp,gif', 'webp:webp,png', 'webp:webp,jpg']),
        ];

        $imageSizeModel = $this->mockClassWithProperties(ImageSizeModel::class, $imageSizeProperties);
        $imageSizeModel
            ->method('row')
            ->willReturn($imageSizeProperties)
        ;

        $imageSizeAdapter = $this->mockConfiguredAdapter(['findByPk' => $imageSizeModel]);

        $imageSizeItemProperties = [
            'width' => 50,
            'height' => 50,
            'resizeMode' => ResizeConfiguration::MODE_CROP,
            'zoom' => 100,
            'sizes' => '50vw',
            'densities' => '0.5x, 2x',
            'media' => '(max-width: 900px)',
        ];

        $imageSizeItemModel = $this->mockClassWithProperties(ImageSizeItemModel::class, $imageSizeItemProperties);
        $imageSizeItemModel
            ->method('row')
            ->willReturn($imageSizeItemProperties)
        ;

        $collection = new Collection([$imageSizeItemModel], 'tl_image_size_item');
        $imageSizeItemAdapter = $this->mockConfiguredAdapter(['findVisibleByPid' => $collection]);

        $adapters = [
            ImageSizeModel::class => $imageSizeAdapter,
            ImageSizeItemModel::class => $imageSizeItemAdapter,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory, $framework);
        $picture = $pictureFactory->create($path, 1);

        $this->assertSame($imageMock, $picture->getImg()['src']);
        $this->assertSame('my-size', $picture->getImg()['class']);
        $this->assertSame('lazy', $picture->getImg()['loading']);
    }

    public function testCorrectlyHandlesEmptyImageFormats(): void
    {
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->anything(),
                $this->callback(
                    function (PictureConfiguration $pictureConfig): bool {
                        $this->assertSame([PictureConfiguration::FORMAT_DEFAULT => [PictureConfiguration::FORMAT_DEFAULT]], $pictureConfig->getFormats());

                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->method('create')
            ->willReturn($imageMock)
        ;

        $imageSizeProperties = [
            'width' => 100,
            'height' => 200,
            'resizeMode' => ResizeConfiguration::MODE_BOX,
            'zoom' => 50,
            'sizes' => '100vw',
            'densities' => '1x, 2x',
            'cssClass' => 'my-size',
            'lazyLoading' => true,
            'formats' => '',
        ];

        $imageSizeModel = $this->mockClassWithProperties(ImageSizeModel::class, $imageSizeProperties);
        $imageSizeModel
            ->method('row')
            ->willReturn($imageSizeProperties)
        ;

        $imageSizeAdapter = $this->mockConfiguredAdapter(['findByPk' => $imageSizeModel]);
        $imageSizeItemAdapter = $this->mockConfiguredAdapter(['findVisibleByPid' => null]);

        $adapters = [
            ImageSizeModel::class => $imageSizeAdapter,
            ImageSizeItemModel::class => $imageSizeItemAdapter,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory, $framework);
        $pictureFactory->create($path, 1);
    }

    public function testCreatesAPictureObjectFromAnImageObjectWithAPredefinedImageSize(): void
    {
        $predefinedSizes = [
            'foobar' => [
                'width' => 100,
                'height' => 200,
                'resizeMode' => ResizeConfiguration::MODE_BOX,
                'zoom' => 50,
                'densities' => '1x, 2x',
                'sizes' => '100vw',
                'cssClass' => 'foobar-class',
                'lazyLoading' => true,
                'skipIfDimensionsMatch' => true,
                'formats' => [
                    'jpg' => ['webp', 'jpg'],
                ],
                'items' => [
                    [
                        'width' => 50,
                        'height' => 50,
                        'resizeMode' => ResizeConfiguration::MODE_BOX,
                        'zoom' => 100,
                        'densities' => '0.5x, 2x',
                        'sizes' => '50vw',
                        'media' => '(max-width: 900px)',
                    ],
                ],
            ],
        ];

        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock): bool {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $config) use ($predefinedSizes): bool {
                        $this->assertSame($predefinedSizes['foobar']['formats']['jpg'], $config->getFormats()['jpg']);

                        $size = $config->getSize();

                        $this->assertSame($predefinedSizes['foobar']['width'], $size->getResizeConfig()->getWidth());
                        $this->assertSame($predefinedSizes['foobar']['height'], $size->getResizeConfig()->getHeight());
                        $this->assertSame($predefinedSizes['foobar']['resizeMode'], $size->getResizeConfig()->getMode());
                        $this->assertSame($predefinedSizes['foobar']['zoom'], $size->getResizeConfig()->getZoomLevel());
                        $this->assertSame($predefinedSizes['foobar']['densities'], $size->getDensities());
                        $this->assertSame($predefinedSizes['foobar']['sizes'], $size->getSizes());
                        $this->assertSame($predefinedSizes['foobar']['sizes'], $size->getSizes());

                        /** @var PictureConfigurationItem $sizeItem */
                        $sizeItem = $config->getSizeItems()[0];

                        $this->assertSame($predefinedSizes['foobar']['items'][0]['width'], $sizeItem->getResizeConfig()->getWidth());
                        $this->assertSame($predefinedSizes['foobar']['items'][0]['height'], $sizeItem->getResizeConfig()->getHeight());
                        $this->assertSame($predefinedSizes['foobar']['items'][0]['resizeMode'], $sizeItem->getResizeConfig()->getMode());
                        $this->assertSame($predefinedSizes['foobar']['items'][0]['zoom'], $sizeItem->getResizeConfig()->getZoomLevel());
                        $this->assertSame($predefinedSizes['foobar']['items'][0]['densities'], $sizeItem->getDensities());
                        $this->assertSame($predefinedSizes['foobar']['items'][0]['sizes'], $sizeItem->getSizes());

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
            ->willReturn($pictureMock)
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator);
        $pictureFactory->setPredefinedSizes($predefinedSizes);

        $picture = $pictureFactory->create($imageMock, [null, null, 'foobar']);

        $this->assertSame($imageMock, $picture->getImg()['src']);
        $this->assertSame($predefinedSizes['foobar']['cssClass'], $picture->getImg()['class']);
        $this->assertSame('lazy', $picture->getImg()['loading']);
    }

    public function testCreatesAPictureObjectFromAnImageObjectWithAPictureConfiguration(): void
    {
        $pictureConfig = (new PictureConfiguration())
            ->setSize(
                (new PictureConfigurationItem())
                    ->setResizeConfig(
                        (new ResizeConfiguration())
                            ->setWidth(100)
                            ->setHeight(200)
                            ->setMode(ResizeConfiguration::MODE_BOX)
                            ->setZoomLevel(50)
                    )
                    ->setDensities('1x, 2x')
                    ->setSizes('100vw')
            )
            ->setSizeItems([
                (new PictureConfigurationItem())
                    ->setResizeConfig(
                        (new ResizeConfiguration())
                            ->setWidth(50)
                            ->setHeight(50)
                            ->setMode(ResizeConfiguration::MODE_CROP)
                            ->setZoomLevel(100)
                    )
                    ->setDensities('0.5x, 2x')
                    ->setSizes('50vw')
                    ->setMedia('(max-width: 900px)'),
            ])
        ;

        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock): bool {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $config) use ($pictureConfig): bool {
                        $this->assertSame($pictureConfig, $config);

                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator);
        $picture = $pictureFactory->create($imageMock, $pictureConfig);

        $this->assertSame($imageMock, $picture->getImg()['src']);
    }

    /**
     * @group legacy
     */
    public function testCreatesAPictureObjectInLegacyMode(): void
    {
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(
                $this->callback(static fn (): bool => true),
                $this->callback(
                    function (PictureConfiguration $config): bool {
                        $this->assertSame($config->getSizeItems(), []);
                        $this->assertSame(
                            ResizeConfiguration::MODE_CROP,
                            $config->getSize()->getResizeConfig()->getMode()
                        );
                        $this->assertSame(100, $config->getSize()->getResizeConfig()->getWidth());
                        $this->assertSame(200, $config->getSize()->getResizeConfig()->getHeight());

                        return true;
                    }
                ),
                $this->callback(static fn (): bool => true)
            )
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->with($this->callback(
                function (string $imagePath) use ($path): bool {
                    $this->assertSame($path, $imagePath);

                    return true;
                }
            ))
            ->willReturn($imageMock)
        ;

        $imageFactory
            ->expects($this->exactly(2))
            ->method('getImportantPartFromLegacyMode')
            ->with(
                $this->callback(static fn (): bool => true),
                $this->callback(
                    function (string $mode): bool {
                        $this->assertSame('left_top', $mode);

                        return true;
                    }
                )
            )
        ;

        $this->expectDeprecation('%slegacy resize mode "left_top" has been deprecated%s');

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory);
        $picture = $pictureFactory->create($path, [100, 200, 'left_top']);
        $pictureFromSerializedConfig = $pictureFactory->create($path, serialize([100, 200, 'left_top']));

        $this->assertSame($imageMock, $picture->getImg()['src']);
        $this->assertSame($imageMock, $pictureFromSerializedConfig->getImg()['src']);
    }

    public function testCreatesAPictureObjectWithoutAModel(): void
    {
        $defaultDensities = '';
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock): bool {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $pictureConfig) use (&$defaultDensities): bool {
                        $this->assertSame(100, $pictureConfig->getSize()->getResizeConfig()->getWidth());
                        $this->assertSame(200, $pictureConfig->getSize()->getResizeConfig()->getHeight());

                        $this->assertSame(
                            ResizeConfiguration::MODE_BOX,
                            $pictureConfig->getSize()->getResizeConfig()->getMode()
                        );

                        $this->assertSame(0, $pictureConfig->getSize()->getResizeConfig()->getZoomLevel());
                        $this->assertSame($defaultDensities, $pictureConfig->getSize()->getDensities());
                        $this->assertSame('', $pictureConfig->getSize()->getSizes());

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
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->with(
                $this->callback(
                    function (string $imagePath) use ($path): bool {
                        $this->assertSame($path, $imagePath);

                        return true;
                    }
                ),
                $this->callback(
                    function (?ResizeConfiguration $size): bool {
                        $this->assertNull($size);

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory);
        $picture = $pictureFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($imageMock, $picture->getImg()['src']);

        $defaultDensities = '1x, 2x';
        $pictureFactory->setDefaultDensities($defaultDensities);
        $picture = $pictureFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($imageMock, $picture->getImg()['src']);
    }

    public function testCreatesAPictureObjectWithEmptyConfig(): void
    {
        $defaultDensities = '';
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock): bool {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $pictureConfig) use (&$defaultDensities): bool {
                        $this->assertTrue($pictureConfig->getSize()->getResizeConfig()->isEmpty());
                        $this->assertSame(0, $pictureConfig->getSize()->getResizeConfig()->getZoomLevel());
                        $this->assertSame($defaultDensities, $pictureConfig->getSize()->getDensities());
                        $this->assertSame('', $pictureConfig->getSize()->getSizes());

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
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->with(
                $this->callback(
                    function (string $imagePath) use ($path): bool {
                        $this->assertSame($path, $imagePath);

                        return true;
                    }
                ),
                $this->callback(
                    function (?ResizeConfiguration $size): bool {
                        $this->assertNull($size);

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory);
        $picture = $pictureFactory->create($path, ['', '', '']);

        $this->assertSame($imageMock, $picture->getImg()['src']);

        $defaultDensities = '1x, 2x';
        $pictureFactory->setDefaultDensities($defaultDensities);
        $picture = $pictureFactory->create($path, [0, 0, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($imageMock, $picture->getImg()['src']);
    }

    /**
     * @dataProvider getResizeOptionsScenarios
     */
    public function testCreatesAPictureWithResizeOptions(ResizeOptions|null $resizeOptions, PictureConfiguration|string|null $size, bool $expected): void
    {
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturnCallback(
                function (ImageInterface $image, PictureConfiguration $config, ResizeOptions $options) use ($imageMock, $expected) {
                    $this->assertSame($expected, $options->getSkipIfDimensionsMatch());

                    return new Picture(['src' => $imageMock, 'srcset' => []], []);
                }
            )
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($imageMock)
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory);
        $pictureFactory->setPredefinedSizes([
            'size_skip' => [
                'resizeMode' => ResizeConfiguration::MODE_BOX,
                'skipIfDimensionsMatch' => true,
                'items' => [],
            ],
            'size_noskip' => [
                'resizeMode' => ResizeConfiguration::MODE_BOX,
                'skipIfDimensionsMatch' => false,
                'items' => [],
            ],
        ]);

        $pictureFactory->create($path, $size, $resizeOptions);
    }

    public function getResizeOptionsScenarios(): \Generator
    {
        yield 'Prefer skipIfDimensionsMatch from explicitly set options (1)' => [
            (new ResizeOptions())->setSkipIfDimensionsMatch(true),
            'size_skip',
            true,
        ];

        yield 'Prefer skipIfDimensionsMatch from explicitly set options (2)' => [
            (new ResizeOptions())->setSkipIfDimensionsMatch(true),
            'size_noskip',
            true,
        ];

        yield 'Prefer skipIfDimensionsMatch from explicitly set options (3)' => [
            (new ResizeOptions())->setSkipIfDimensionsMatch(false),
            'size_skip',
            false,
        ];

        yield 'Prefer skipIfDimensionsMatch from explicitly set options (4)' => [
            (new ResizeOptions())->setSkipIfDimensionsMatch(false),
            'size_noskip',
            false,
        ];

        yield 'Use skipIfDimensionsMatch from predefined size (1)' => [
            null,
            'size_skip',
            true,
        ];

        yield 'Use skipIfDimensionsMatch from predefined size (2)' => [
            null,
            'size_noskip',
            false,
        ];

        yield 'Fallback to default resize option when passing a picture configuration' => [
            null,
            new PictureConfiguration(),
            false,
        ];

        yield 'Fallback to default predefined size' => [
            null,
            null,
            true,
        ];
    }

    /**
     * @dataProvider getAspectRatios
     */
    public function testSetHasSingleAspectRatioAttribute(bool $expected, int $imgWidth, int $imgHeight, int $sourceWidth, int $sourceHeight): void
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureConfig = $this->createMock(PictureConfiguration::class);
        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);

        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturnCallback(
                static fn (ImageInterface $image, PictureConfiguration $config): Picture => new Picture(
                    [
                        'src' => $imageMock,
                        'srcset' => [[$imageMock]],
                        'width' => $imgWidth,
                        'height' => $imgHeight,
                    ],
                    [[
                        'src' => $imageMock,
                        'srcset' => [[$imageMock]],
                        'width' => $sourceWidth,
                        'height' => $sourceHeight,
                    ]]
                )
            )
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator);
        $picture = $pictureFactory->create($imageMock, $pictureConfig);

        $this->assertSame($expected, $picture->getImg()['hasSingleAspectRatio']);
    }

    public function getAspectRatios(): \Generator
    {
        yield [true, 100, 100, 50, 50];
        yield [true, 100, 100, 101, 100];
        yield [true, 100, 100, 100, 101];
        yield [true, 100, 100, 105, 100];
        yield [true, 100, 100, 100, 105];
        yield [true, 100, 10, 105, 10];
        yield [true, 10, 100, 10, 105];
        yield [true, 100, 10, 95, 10];
        yield [true, 10, 100, 10, 95];
        yield [true, 100, 20, 100, 21];
        yield [true, 20, 100, 21, 100];
        yield [false, 100, 100, 100, 50];
        yield [false, 100, 100, 106, 100];
        yield [false, 100, 100, 100, 106];
        yield [false, 100, 100, 94, 100];
        yield [false, 100, 100, 100, 94];
        yield [false, 100, 10, 106, 10];
        yield [false, 10, 100, 10, 106];
        yield [false, 100, 20, 100, 22];
        yield [false, 20, 100, 22, 100];
    }

    private function getPictureFactory(PictureGeneratorInterface|null $pictureGenerator = null, ImageFactoryInterface|null $imageFactory = null, ContaoFramework|null $framework = null): PictureFactory
    {
        $pictureGenerator ??= $this->createMock(PictureGeneratorInterface::class);
        $imageFactory ??= $this->createMock(ImageFactoryInterface::class);
        $framework ??= $this->createMock(ContaoFramework::class);

        return new PictureFactory($pictureGenerator, $imageFactory, $framework, false, []);
    }
}

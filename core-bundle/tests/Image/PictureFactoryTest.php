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
use Contao\Image\PictureInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;
use Contao\Model\Collection;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;

class PictureFactoryTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    public function testCreatesAPictureObjectFromAnImagePath(): void
    {
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
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

                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
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
        ];

        /** @var ImageSizeModel&MockObject $imageSizeModel */
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

        /** @var ImageSizeItemModel&MockObject $imageSizeItemModel */
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

        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
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

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
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

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory, $this->mockContaoFramework());
        $pictureFactory->setPredefinedSizes($predefinedSizes);

        $picture = $pictureFactory->create($imageMock, [null, null, 'foobar']);

        $this->assertSame($imageMock, $picture->getImg()['src']);
        $this->assertSame($predefinedSizes['foobar']['cssClass'], $picture->getImg()['class']);
    }

    public function testCreatesAPictureObjectFromAnImageObjectWithAPictureConfiguration(): void
    {
        $pictureConfig = (new PictureConfiguration())
            ->setSize((new PictureConfigurationItem())
                ->setResizeConfig((new ResizeConfiguration())
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
                    ->setResizeConfig((new ResizeConfiguration())
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
        $pictureMock = $this->createMock(PictureInterface::class);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
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

        $this->assertSame($pictureMock, $picture);
    }

    public function testCreatesAPictureObjectInLegacyMode(): void
    {
        $path = $this->getTempDir().'/images/dummy.jpg';
        $pictureMock = $this->createMock(PictureInterface::class);
        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(
                    static function (): bool {
                        return true;
                    }
                ),
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
                $this->callback(
                    static function (): bool {
                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $imageMock = $this->createMock(ImageInterface::class);

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
                )
            )
            ->willReturn($imageMock)
        ;

        $imageFactory
            ->expects($this->once())
            ->method('getImportantPartFromLegacyMode')
            ->with(
                $this->callback(
                    static function (): bool {
                        return true;
                    }
                ),
                $this->callback(
                    function (string $mode): bool {
                        $this->assertSame('left_top', $mode);

                        return true;
                    }
                )
            )
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory);
        $picture = $pictureFactory->create($path, [100, 200, 'left_top']);

        $this->assertSame($pictureMock, $picture);
    }

    public function testCreatesAPictureObjectWithoutAModel(): void
    {
        $defaultDensities = '';
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = $this->createMock(PictureInterface::class);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
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

        $this->assertSame($pictureMock, $picture);

        $defaultDensities = '1x, 2x';
        $pictureFactory->setDefaultDensities($defaultDensities);
        $picture = $pictureFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($pictureMock, $picture);
    }

    public function testCreatesAPictureObjectWithEmptyConfig(): void
    {
        $defaultDensities = '';
        $path = $this->getTempDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = $this->createMock(PictureInterface::class);

        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        $pictureGenerator
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

        $this->assertSame($pictureMock, $picture);

        $defaultDensities = '1x, 2x';
        $pictureFactory->setDefaultDensities($defaultDensities);
        $picture = $pictureFactory->create($path, [0, 0, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($pictureMock, $picture);
    }

    /**
     * @param PictureGeneratorInterface&MockObject $pictureGenerator
     * @param ImageFactoryInterface&MockObject     $imageFactory
     * @param ContaoFramework&MockObject           $framework
     */
    private function getPictureFactory(PictureGeneratorInterface $pictureGenerator = null, ImageFactoryInterface $imageFactory = null, ContaoFramework $framework = null, bool $bypassCache = null, array $imagineOptions = null): PictureFactory
    {
        if (null === $pictureGenerator) {
            $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        }

        if (null === $imageFactory) {
            $imageFactory = $this->createMock(ImageFactoryInterface::class);
        }

        if (null === $framework) {
            $framework = $this->createMock(ContaoFramework::class);
        }

        if (null === $bypassCache) {
            $bypassCache = false;
        }

        if (null === $imagineOptions) {
            $imagineOptions = [];
        }

        return new PictureFactory($pictureGenerator, $imageFactory, $framework, $bypassCache, $imagineOptions);
    }
}

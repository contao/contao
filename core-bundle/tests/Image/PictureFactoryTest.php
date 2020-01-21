<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\ImageInterface;
use Contao\Image\Picture;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationInterface;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\PictureGenerator;
use Contao\Image\PictureGeneratorInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;
use Contao\Model\Collection;

/**
 * Tests the PictureFactory class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureFactoryTest extends TestCase
{
    /**
     * Tests the create() method.
     */
    public function testCreatesAPictureObjectFromAnImagePath()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);
        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);

        $pictureGenerator
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock) {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $pictureConfig) {
                        $size = $pictureConfig->getSize();

                        $this->assertSame(100, $size->getResizeConfig()->getWidth());
                        $this->assertSame(200, $size->getResizeConfig()->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_BOX, $size->getResizeConfig()->getMode());
                        $this->assertSame(50, $size->getResizeConfig()->getZoomLevel());
                        $this->assertSame('1x, 2x', $size->getDensities());
                        $this->assertSame('100vw', $size->getSizes());

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
                    function ($imagePath) use ($path) {
                        $this->assertSame($path, $imagePath);

                        return true;
                    }
                ),
                $this->callback(
                    function ($size) {
                        $this->assertNull($size);

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $imageSizeModel = $this->createMock(ImageSizeModel::class);

        $imageSizeModel
            ->method('__get')
            ->willReturnCallback(
                function ($key) {
                    return [
                        'width' => '100',
                        'height' => '200',
                        'resizeMode' => ResizeConfiguration::MODE_BOX,
                        'zoom' => '50',
                        'sizes' => '100vw',
                        'densities' => '1x, 2x',
                        'cssClass' => 'my-size',
                    ][$key];
                }
            )
        ;

        $imageSizeAdapter = $this->createMock(Adapter::class);

        $imageSizeAdapter
            ->method('__call')
            ->willReturn($imageSizeModel)
        ;

        $imageSizeItemModel = $this->createMock(ImageSizeItemModel::class);

        $imageSizeItemModel
            ->method('__get')
            ->willReturnCallback(
                function ($key) {
                    return [
                        'width' => '50',
                        'height' => '50',
                        'resizeMode' => ResizeConfiguration::MODE_CROP,
                        'zoom' => '100',
                        'sizes' => '50vw',
                        'densities' => '0.5x, 2x',
                        'media' => '(max-width: 900px)',
                    ][$key];
                }
            )
        ;

        $imageSizeItemModel
            ->method('__isset')
            ->willReturn(true)
        ;

        $imageSizeItemAdapter = $this->createMock(Adapter::class);

        $imageSizeItemAdapter
            ->method('__call')
            ->willReturn(new Collection([$imageSizeItemModel], 'tl_image_size_item'))
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function ($key) use ($imageSizeAdapter, $imageSizeItemAdapter) {
                    return [
                        ImageSizeModel::class => $imageSizeAdapter,
                        ImageSizeItemModel::class => $imageSizeItemAdapter,
                    ][$key];
                }
            )
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory, $framework);
        $picture = $pictureFactory->create($path, 1);

        $this->assertSame($imageMock, $picture->getImg()['src']);
        $this->assertSame('my-size', $picture->getImg()['class']);
    }

    /**
     * Tests the create() method.
     */
    public function testCreatesAPictureObjectFromAnImageObjectWithAPictureConfiguration()
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
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock) {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfigurationInterface $config) use ($pictureConfig) {
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
     * Tests the create() method.
     */
    public function testCreatesAPictureObjectInLegacyMode()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';

        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);
        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);

        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(
                    function () {
                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfigurationInterface $config) {
                        $this->assertSame($config->getSizeItems(), []);
                        $this->assertSame(
                            ResizeConfigurationInterface::MODE_CROP,
                            $config->getSize()->getResizeConfig()->getMode()
                        );
                        $this->assertSame(100, $config->getSize()->getResizeConfig()->getWidth());
                        $this->assertSame(200, $config->getSize()->getResizeConfig()->getHeight());

                        return true;
                    }
                ),
                $this->callback(
                    function () {
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
                    function ($imagePath) use ($path) {
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
                    function () {
                        return true;
                    }
                ),
                $this->callback(
                    function ($mode) {
                        $this->assertSame('left_top', $mode);

                        return true;
                    }
                )
            )
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator, $imageFactory);
        $picture = $pictureFactory->create($path, [100, 200, 'left_top']);

        $this->assertSame($imageMock, $picture->getImg()['src']);
    }

    /**
     * Tests the create() method.
     */
    public function testCreatesAPictureObjectWithoutAModel()
    {
        $defaultDensities = '';
        $path = $this->getRootDir().'/images/dummy.jpg';

        $imageMock = $this->createMock(ImageInterface::class);
        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);
        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);

        $pictureGenerator
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) use ($imageMock) {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfigurationInterface $pictureConfig) use (&$defaultDensities) {
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
                )
            )
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);

        $imageFactory
            ->method('create')
            ->with(
                $this->callback(
                    function ($imagePath) use ($path) {
                        $this->assertSame($path, $imagePath);

                        return true;
                    }
                ),
                $this->callback(
                    function ($size) {
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

    /**
     * Tests that the set has a single aspect ratio attribute.
     *
     * @param bool $expected
     * @param int  $imgWidth
     * @param int  $imgHeight
     * @param int  $sourceWidth
     * @param int  $sourceHeight
     *
     * @dataProvider getAspectRatios
     */
    public function testSetsHasSingleAspectRatioAttribute($expected, $imgWidth, $imgHeight, $sourceWidth, $sourceHeight)
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $pictureConfig = $this->createMock(PictureConfigurationInterface::class);
        $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);

        $pictureGenerator
            ->method('generate')
            ->willReturnCallback(
                static function (ImageInterface $image, PictureConfigurationInterface $config) use ($imageMock, $imgWidth, $imgHeight, $sourceWidth, $sourceHeight) {
                    return new Picture(
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
                    );
                }
            )
        ;

        $pictureFactory = $this->getPictureFactory($pictureGenerator);
        $picture = $pictureFactory->create($imageMock, $pictureConfig);

        $this->assertSame($expected, $picture->getImg()['hasSingleAspectRatio']);
    }

    /**
     * Provides the data for the testSetsHasSingleAspectRatioAttribute() method.
     *
     * @return array
     */
    public function getAspectRatios()
    {
        return [
            [true, 100, 100, 50, 50],
            [true, 100, 100, 101, 100],
            [true, 100, 100, 100, 101],
            [true, 100, 100, 105, 100],
            [true, 100, 100, 100, 105],
            [true, 100, 10, 105, 10],
            [true, 10, 100, 10, 105],
            [true, 100, 10, 95, 10],
            [true, 10, 100, 10, 95],
            [true, 100, 20, 100, 21],
            [true, 20, 100, 21, 100],
            [false, 100, 100, 100, 50],
            [false, 100, 100, 106, 100],
            [false, 100, 100, 100, 106],
            [false, 100, 100, 94, 100],
            [false, 100, 100, 100, 94],
            [false, 100, 10, 106, 10],
            [false, 10, 100, 10, 106],
            [false, 100, 20, 100, 22],
            [false, 20, 100, 22, 100],
        ];
    }

    /**
     * Creates an PictureFactory instance helper.
     *
     * @param PictureGenerator|null         $pictureGenerator
     * @param ImageFactory|null             $imageFactory
     * @param ContaoFrameworkInterface|null $framework
     * @param bool                          $bypassCache
     * @param array                         $imagineOptions
     *
     * @return PictureFactory
     */
    private function getPictureFactory($pictureGenerator = null, $imageFactory = null, $framework = null, $bypassCache = null, $imagineOptions = null)
    {
        if (null === $pictureGenerator) {
            $pictureGenerator = $this->createMock(PictureGeneratorInterface::class);
        }

        if (null === $imageFactory) {
            $imageFactory = $this->createMock(ImageFactoryInterface::class);
        }

        if (null === $framework) {
            $framework = $this->createMock(ContaoFrameworkInterface::class);
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

<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Image;

use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Test\TestCase;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image\Image;
use Contao\Image\ImageInterface;
use Contao\Image\Picture;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationInterface;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeOptionsInterface;
use Contao\Model\Collection;

/**
 * Tests the PictureFactory class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureFactoryTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pictureFactory = $this->createPictureFactory();

        $this->assertInstanceOf('Contao\CoreBundle\Image\PictureFactory', $pictureFactory);
        $this->assertInstanceOf('Contao\CoreBundle\Image\PictureFactoryInterface', $pictureFactory);
    }

    /**
     * Tests the create() method.
     */
    public function testCreate()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';

        $imageMock = $this
            ->getMockBuilder('Contao\Image\Image')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureMock = new Picture(['src' => $imageMock, 'srcset' => []], []);

        $pictureGenerator = $this
            ->getMockBuilder('Contao\Image\PictureGenerator')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureGenerator
            ->expects($this->any())
            ->method('generate')
            ->with(
                $this->callback(
                    function (Image $image) use ($imageMock) {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $pictureConfig) {
                        $size = $pictureConfig->getSize();

                        $this->assertEquals(100, $size->getResizeConfig()->getWidth());
                        $this->assertEquals(200, $size->getResizeConfig()->getHeight());
                        $this->assertEquals(ResizeConfiguration::MODE_BOX, $size->getResizeConfig()->getMode());
                        $this->assertEquals(50, $size->getResizeConfig()->getZoomLevel());
                        $this->assertEquals('1x, 2x', $size->getDensities());
                        $this->assertEquals('100vw', $size->getSizes());

                        $sizeItem = $pictureConfig->getSizeItems()[0];

                        $this->assertEquals(50, $sizeItem->getResizeConfig()->getWidth());
                        $this->assertEquals(50, $sizeItem->getResizeConfig()->getHeight());
                        $this->assertEquals(ResizeConfiguration::MODE_CROP, $sizeItem->getResizeConfig()->getMode());
                        $this->assertEquals(100, $sizeItem->getResizeConfig()->getZoomLevel());
                        $this->assertEquals('0.5x, 2x', $sizeItem->getDensities());
                        $this->assertEquals('50vw', $sizeItem->getSizes());
                        $this->assertEquals('(max-width: 900px)', $sizeItem->getMedia());

                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this
            ->getMockBuilder('Contao\CoreBundle\Image\ImageFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $imageFactory
            ->expects($this->any())
            ->method('create')
            ->with(
                $this->callback(
                    function ($imagePath) use ($path) {
                        $this->assertEquals($path, $imagePath);

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

        $framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $imageSizeModel = $this->getMock('Contao\ImageSizeModel');

        $imageSizeModel
            ->expects($this->any())
            ->method('__get')
            ->will(
                $this->returnCallback(function ($key) {
                    return [
                        'width' => '100',
                        'height' => '200',
                        'resizeMode' => ResizeConfiguration::MODE_BOX,
                        'zoom' => '50',
                        'sizes' => '100vw',
                        'densities' => '1x, 2x',
                        'cssClass' => 'my-size',
                    ][$key];
                })
            )
        ;

        $imageSizeAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $imageSizeAdapter
            ->expects($this->any())
            ->method('__call')
            ->willReturn($imageSizeModel)
        ;

        $imageSizeItemModel = $this->getMock('Contao\ImageSizeItemModel');

        $imageSizeItemModel
            ->expects($this->any())
            ->method('__get')
            ->will(
                $this->returnCallback(function ($key) {
                    return [
                        'width' => '50',
                        'height' => '50',
                        'resizeMode' => ResizeConfiguration::MODE_CROP,
                        'zoom' => '100',
                        'sizes' => '50vw',
                        'densities' => '0.5x, 2x',
                        'media' => '(max-width: 900px)',
                    ][$key];
                })
            )
        ;

        $imageSizeItemModel
            ->expects($this->any())
            ->method('__isset')
            ->willReturn(true)
        ;

        $imageSizeItemAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $imageSizeItemAdapter
            ->expects($this->any())
            ->method('__call')
            ->willReturn(new Collection([$imageSizeItemModel], 'tl_image_size_item'))
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->will(
                $this->returnCallback(function ($key) use ($imageSizeAdapter, $imageSizeItemAdapter) {
                    return [
                        'Contao\ImageSizeModel' => $imageSizeAdapter,
                        'Contao\ImageSizeItemModel' => $imageSizeItemAdapter,
                    ][$key];
                })
            )
        ;

        $pictureFactory = $this->createPictureFactory($pictureGenerator, $imageFactory, $framework);
        $picture = $pictureFactory->create($path, 1);

        $this->assertSame($imageMock, $picture->getImg()['src']);
        $this->assertEquals('my-size', $picture->getImg()['class']);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithImageObjectAndPictureConfiguration()
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

        $imageMock = $this
            ->getMockBuilder('Contao\Image\Image')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureMock = $this
            ->getMockBuilder('Contao\Image\Picture')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureGenerator = $this
            ->getMockBuilder('Contao\Image\PictureGenerator')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureGenerator
            ->expects($this->any())
            ->method('generate')
            ->with(
                $this->callback(
                    function (Image $image) use ($imageMock) {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $config) use ($pictureConfig) {
                        $this->assertSame($pictureConfig, $config);

                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $pictureFactory = $this->createPictureFactory($pictureGenerator);
        $picture = $pictureFactory->create($imageMock, $pictureConfig);

        $this->assertSame($pictureMock, $picture);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateLegacyMode()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';

        $pictureMock = $this
            ->getMockBuilder('Contao\Image\Picture')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureGenerator = $this
            ->getMockBuilder('Contao\Image\PictureGenerator')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                $this->callback(
                    function (ImageInterface $image) {
                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfigurationInterface $config) {
                        $this->assertEquals($config->getSizeItems(), []);
                        $this->assertEquals(
                            ResizeConfigurationInterface::MODE_CROP,
                            $config->getSize()->getResizeConfig()->getMode()
                        );
                        $this->assertEquals(100, $config->getSize()->getResizeConfig()->getWidth());
                        $this->assertEquals(200, $config->getSize()->getResizeConfig()->getHeight());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeOptionsInterface $options) {
                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $imageMock = $this
            ->getMockBuilder('Contao\Image\Image')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $imageFactory = $this
            ->getMockBuilder('Contao\CoreBundle\Image\ImageFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $imageFactory
            ->expects($this->once())
            ->method('create')
            ->with(
                $this->callback(
                    function ($imagePath) use ($path) {
                        $this->assertEquals($path, $imagePath);

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
                    function (ImageInterface $image) {
                        return true;
                    }
                ),
                $this->callback(
                    function ($mode) {
                        $this->assertEquals('left_top', $mode);

                        return true;
                    }
                )
            )
        ;

        $pictureFactory = $this->createPictureFactory($pictureGenerator, $imageFactory);
        $picture = $pictureFactory->create($path, [100, 200, 'left_top']);

        $this->assertSame($pictureMock, $picture);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithoutModel()
    {
        $defaultDensities = '';
        $path = $this->getRootDir().'/images/dummy.jpg';

        $imageMock = $this
            ->getMockBuilder('Contao\Image\Image')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureMock = $this
            ->getMockBuilder('Contao\Image\Picture')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureGenerator = $this
            ->getMockBuilder('Contao\Image\PictureGenerator')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pictureGenerator
            ->expects($this->any())
            ->method('generate')
            ->with(
                $this->callback(
                    function (Image $image) use ($imageMock) {
                        $this->assertSame($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (PictureConfiguration $pictureConfig) use (&$defaultDensities) {
                        $this->assertEquals(100, $pictureConfig->getSize()->getResizeConfig()->getWidth());
                        $this->assertEquals(200, $pictureConfig->getSize()->getResizeConfig()->getHeight());

                        $this->assertEquals(
                            ResizeConfiguration::MODE_BOX,
                            $pictureConfig->getSize()->getResizeConfig()->getMode()
                        );

                        $this->assertEquals(0, $pictureConfig->getSize()->getResizeConfig()->getZoomLevel());
                        $this->assertEquals($defaultDensities, $pictureConfig->getSize()->getDensities());
                        $this->assertEquals('', $pictureConfig->getSize()->getSizes());

                        return true;
                    }
                )
            )
            ->willReturn($pictureMock)
        ;

        $imageFactory = $this
            ->getMockBuilder('Contao\CoreBundle\Image\ImageFactory')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $imageFactory
            ->expects($this->any())
            ->method('create')
            ->with(
                $this->callback(
                    function ($imagePath) use ($path) {
                        $this->assertEquals($path, $imagePath);

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

        $pictureFactory = $this->createPictureFactory($pictureGenerator, $imageFactory);
        $picture = $pictureFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($pictureMock, $picture);

        $defaultDensities = '1x, 2x';
        $pictureFactory->setDefaultDensities($defaultDensities);
        $picture = $pictureFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($pictureMock, $picture);
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
    private function createPictureFactory($pictureGenerator = null, $imageFactory = null, $framework = null, $bypassCache = null, $imagineOptions = null)
    {
        if (null === $pictureGenerator) {
            $pictureGenerator = $this
                ->getMockBuilder('Contao\Image\PictureGenerator')
                ->disableOriginalConstructor()
                ->getMock()
            ;
        }

        if (null === $imageFactory) {
            $imageFactory = $this
                ->getMockBuilder('Contao\CoreBundle\Image\ImageFactory')
                ->disableOriginalConstructor()
                ->getMock()
            ;
        }

        if (null === $framework) {
            $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');
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

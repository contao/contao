<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Image;

use Contao\CoreBundle\Test\TestCase;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image\ImportantPart;
use Contao\Image\Image;
use Contao\Image\Resizer;
use Contao\Image\ResizeConfiguration;
use Contao\Image\PictureConfiguration;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Image\Box;
use Imagine\Image\Point;

/**
 * Tests the PictureFactory class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureFactoryTest extends TestCase
{
    /**
     * Create an PictureFactory instance helper.
     *
     * @param Resizer                  $resizer
     * @param ImagineInterface         $imagine
     * @param Filesystem               $filesystem
     * @param ContaoFrameworkInterface $framework
     * @param array                    $imagineOptions
     *
     * @return PictureFactory
     */
    private function createPictureFactory($pictureGenerator = null, $imageFactory = null, $framework = null, $imagineOptions = null)
    {
        if (null === $pictureGenerator) {
            $pictureGenerator = $this->getMockBuilder('Contao\Image\PictureGenerator')
             ->disableOriginalConstructor()
             ->getMock();
        }

        if (null === $imageFactory) {
            $imageFactory = $this->getMockBuilder('Contao\CoreBundle\Image\ImageFactory')
             ->disableOriginalConstructor()
             ->getMock();
        }

        if (null === $framework) {
            $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');
        }

        if (null === $imagineOptions) {
            $imagineOptions = [];
        }

        return new PictureFactory($pictureGenerator, $imageFactory, $framework, $imagineOptions);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Image\PictureFactory', $this->createPictureFactory());
    }

    /**
     * Tests the create() method.
     */
    public function testCreate()
    {
        $path = $this->getRootDir() . '/images/dummy.jpg';

        $imageMock = $this->getMockBuilder('Contao\Image\Image')
            ->disableOriginalConstructor()
            ->getMock();

        $pictureMock = $this->getMockBuilder('Contao\Image\Picture')
             ->disableOriginalConstructor()
             ->getMock();

        $pictureGenerator = $this->getMockBuilder('Contao\Image\PictureGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $pictureGenerator->expects($this->any())
            ->method('generate')
            ->with(
                $this->callback(function (Image $image) use ($imageMock) {
                    $this->assertSame($imageMock, $image);

                    return true;
                }),
                $this->callback(function (PictureConfiguration $pictureConfig) {
                    $this->assertEquals(100, $pictureConfig->getSize()->getResizeConfig()->getWidth());
                    $this->assertEquals(200, $pictureConfig->getSize()->getResizeConfig()->getHeight());
                    $this->assertEquals(ResizeConfiguration::MODE_BOX, $pictureConfig->getSize()->getResizeConfig()->getMode());
                    $this->assertEquals(50, $pictureConfig->getSize()->getResizeConfig()->getZoomLevel());
                    $this->assertEquals('1x, 2x', $pictureConfig->getSize()->getDensities());
                    $this->assertEquals('100vw', $pictureConfig->getSize()->getSizes());

                    return true;
                })
            )
            ->willReturn($pictureMock);

        $imageFactory = $this->getMockBuilder('Contao\CoreBundle\Image\ImageFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $imageFactory->expects($this->any())
            ->method('create')
            ->with(
                $this->callback(function ($imagePath) use ($path) {
                    $this->assertEquals($path, $imagePath);

                    return true;
                }),
                $this->callback(function ($size) {
                    $this->assertNull($size);

                    return true;
                })
            )
            ->willReturn($imageMock);

        $framework = $this->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->getMock();

        $imageSizeModel = $this->getMock('Contao\ImageSizeModel');

        $imageSizeModel->expects($this->any())
            ->method('__get')
            ->will($this->returnCallback(function ($key) {
                return [
                    'width' => '100',
                    'height' => '200',
                    'resizeMode' => ResizeConfiguration::MODE_BOX,
                    'zoom' => '50',
                    'sizes' => '100vw',
                    'densities' => '1x, 2x',
                ][$key];
            }));

        $imageSizeAdapter = $this->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $imageSizeAdapter->expects($this->any())
            ->method('__call')
            ->willReturn($imageSizeModel);

        $framework->expects($this->any())
            ->method('getAdapter')
            ->willReturn($imageSizeAdapter);

        $pictureFactory = $this->createPictureFactory($pictureGenerator, $imageFactory, $framework);

        $picture = $pictureFactory->create($path, 1);

        $this->assertSame($pictureMock, $picture);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateLegacyMode()
    {
        $path = $this->getRootDir() . '/images/dummy.jpg';

        $pictureMock = $this->getMockBuilder('Contao\Image\Picture')
             ->disableOriginalConstructor()
             ->getMock();

        $pictureGenerator = $this->getMockBuilder('Contao\Image\PictureGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $pictureGenerator->expects($this->any())
            ->method('generate')
            ->willReturn($pictureMock);

        $imageMock = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $imageFactory = $this->getMockBuilder('Contao\CoreBundle\Image\ImageFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $imageFactory->expects($this->any())
            ->method('create')
            ->with(
                $this->callback(function ($imagePath) use ($path) {
                    $this->assertEquals($path, $imagePath);

                    return true;
                }),
                $this->callback(function ($size) {
                    $this->assertEquals([100, 200, 'left_top'], $size);

                    return true;
                })
            )
            ->willReturn($imageMock);

        $pictureFactory = $this->createPictureFactory($pictureGenerator, $imageFactory);

        $picture = $pictureFactory->create($path, [100, 200, 'left_top']);

        $this->assertSame($pictureMock, $picture);
    }
}

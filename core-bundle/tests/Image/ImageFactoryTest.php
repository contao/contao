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
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\Resizer;
use Contao\CoreBundle\Image\ResizeConfiguration;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Image\Box;

/**
 * Tests the ImageFactory class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageFactoryTest extends TestCase
{
    /**
     * Create an ImageFactory instance helper.
     *
     * @param Resizer                  $resizer
     * @param ImagineInterface         $imagine
     * @param Filesystem               $filesystem
     * @param ContaoFrameworkInterface $framework
     *
     * @return ImageFactory
     */
    private function createImageFactory($resizer = null, $imagine = null, $imagineSvg = null, $filesystem = null, $framework = null)
    {
        if (null === $resizer) {
            $resizer = $this->getMockBuilder('Contao\CoreBundle\Image\Resizer')
             ->disableOriginalConstructor()
             ->getMock();
        }

        if (null === $imagine) {
            $imagine = $this->getMock('Imagine\Image\ImagineInterface');
        }

        if (null === $imagineSvg) {
            $imagineSvg = $this->getMock('Imagine\Image\ImagineInterface');
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (null === $framework) {
            $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');
        }

        return new ImageFactory($resizer, $imagine, $imagineSvg, $filesystem, $framework);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Image\ImageFactory', $this->createImageFactory());
    }

    /**
     * Tests the create() method.
     */
    public function testCreate()
    {
        $path = $this->getRootDir() . '/images/dummy.jpg';

        $imageMock = $this->getMockBuilder('Contao\CoreBundle\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer = $this->getMockBuilder('Contao\CoreBundle\Image\Resizer')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(function ($image) use ($path) {
                    $this->assertEquals($path, $image->getPath());

                    return true;
                }),
                $this->callback(function ($config) {
                    $this->assertEquals(100, $config->getWidth());
                    $this->assertEquals(200, $config->getHeight());
                    $this->assertEquals(ResizeConfiguration::MODE_BOX, $config->getMode());

                    return true;
                })
            )
            ->willReturn($imageMock);

        $imagineImageMock = $this->getMock('Imagine\Image\ImageInterface');

        $imagineImageMock
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(new Box(100, 100));

        $imagine = $this->getMock('Imagine\Image\ImagineInterface');

        $imagine
            ->expects($this->once())
            ->method('open')
            ->willReturn($imagineImageMock);

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine);

        $image = $imageFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($imageMock, $image);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithImageSize()
    {
        $path = $this->getRootDir() . '/images/dummy.jpg';

        $imageMock = $this->getMockBuilder('Contao\CoreBundle\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer = $this->getMockBuilder('Contao\CoreBundle\Image\Resizer')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(function ($image) use ($path) {
                    $this->assertEquals($path, $image->getPath());

                    return true;
                }),
                $this->callback(function ($config) {
                    $this->assertEquals(100, $config->getWidth());
                    $this->assertEquals(200, $config->getHeight());
                    $this->assertEquals(ResizeConfiguration::MODE_BOX, $config->getMode());

                    return true;
                }),
                $this->equalTo('target/path.jpg')
            )
            ->willReturn($imageMock);

        $imagineImageMock = $this->getMock('Imagine\Image\ImageInterface');

        $imagineImageMock
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(new Box(100, 100));

        $imagine = $this->getMock('Imagine\Image\ImagineInterface');

        $imagine
            ->expects($this->once())
            ->method('open')
            ->willReturn($imagineImageMock);

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

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine, null, $framework);

        $image = $imageFactory->create($path, 1, 'target/path.jpg');

        $this->assertSame($imageMock, $image);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithoutResize()
    {
        $imageFactory = $this->createImageFactory();

        $image = $imageFactory->create($this->getRootDir() . '/images/dummy.jpg');

        $this->assertEquals($this->getRootDir() . '/images/dummy.jpg', $image->getPath());
    }
}

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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image\ImportantPart;
use Contao\Image\Resizer;
use Contao\Image\ResizeConfiguration;
use Symfony\Component\Filesystem\Filesystem;
use Imagine\Image\Box;
use Imagine\Image\Point;

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
     * @param bool                     $bypassCache
     * @param array                    $imagineOptions
     *
     * @return ImageFactory
     */
    private function createImageFactory($resizer = null, $imagine = null, $imagineSvg = null, $filesystem = null, $framework = null, $bypassCache = null, $imagineOptions = null)
    {
        if (null === $resizer) {
            $resizer = $this->getMockBuilder('Contao\Image\Resizer')
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

        if (null === $bypassCache) {
            $bypassCache = false;
        }

        if (null === $imagineOptions) {
            $imagineOptions = ['jpeg_quality' => 80];
        }

        return new ImageFactory($resizer, $imagine, $imagineSvg, $filesystem, $framework, $bypassCache, $imagineOptions);
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

        $imageMock = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer = $this->getMockBuilder('Contao\Image\Resizer')
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

        $framework = $this->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->getMock();

        $filesModel = $this->getMock('Contao\FilesModel');

        $filesModel->expects($this->any())
            ->method('__get')
            ->willReturn(null);

        $filesAdapter = $this->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $filesAdapter->expects($this->any())
            ->method('__call')
            ->willReturn($filesModel);

        $framework->expects($this->any())
            ->method('getAdapter')
            ->willReturn($filesAdapter);

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine, null, $framework);

        $image = $imageFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSame($imageMock, $image);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithImageSize()
    {
        $path = $this->getRootDir() . '/images/dummy.jpg';

        $imageMock = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer = $this->getMockBuilder('Contao\Image\Resizer')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(function ($image) use ($path) {
                    $this->assertEquals($path, $image->getPath());
                    $this->assertEquals(new ImportantPart(
                        new Point(50, 50),
                        new Box(25, 25)
                    ), $image->getImportantPart());

                    return true;
                }),
                $this->callback(function ($config) {
                    $this->assertEquals(100, $config->getWidth());
                    $this->assertEquals(200, $config->getHeight());
                    $this->assertEquals(ResizeConfiguration::MODE_BOX, $config->getMode());

                    return true;
                }),
                $this->equalTo(['jpeg_quality' => 80]),
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

        $filesModel = $this->getMock('Contao\FilesModel');

        $filesModel->expects($this->any())
            ->method('__get')
            ->will($this->returnCallback(function ($key) {
                return [
                    'importantPartX' => '50',
                    'importantPartY' => '50',
                    'importantPartWidth' => '25',
                    'importantPartHeight' => '25',
                ][$key];
            }));

        $filesAdapter = $this->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $filesAdapter->expects($this->any())
            ->method('__call')
            ->willReturn($filesModel);

        $framework->expects($this->any())
            ->method('getAdapter')
            ->will($this->returnCallback(function($key) use($imageSizeAdapter, $filesAdapter) {
                return [
                    'Contao\\ImageSizeModel' => $imageSizeAdapter,
                    'Contao\\FilesModel' => $filesAdapter,
                ][$key];
            }));

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine, null, $framework);

        $image = $imageFactory->create($path, 1, 'target/path.jpg');

        $this->assertSame($imageMock, $image);
    }

    /**
     * Tests the create() method.
     *
     * @dataProvider getCreateWithLegacyMode
     */
    public function testCreateWithLegacyMode($mode, $expected)
    {
        $path = $this->getRootDir() . '/images/dummy.jpg';

        $imageMock = $this->getMockBuilder('Contao\Image\Image')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer = $this->getMockBuilder('Contao\Image\Resizer')
             ->disableOriginalConstructor()
             ->getMock();

        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(function ($image) use ($path, $expected) {
                    $this->assertEquals($path, $image->getPath());
                    $this->assertEquals(new ImportantPart(
                        new Point($expected[0], $expected[1]),
                        new Box($expected[2], $expected[3])
                    ), $image->getImportantPart());

                    return true;
                }),
                $this->callback(function ($config) {
                    $this->assertEquals(50, $config->getWidth());
                    $this->assertEquals(50, $config->getHeight());
                    $this->assertEquals(ResizeConfiguration::MODE_CROP, $config->getMode());
                    $this->assertEquals(0, $config->getZoomLevel());

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

        $framework = $this->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->getMock();

        $filesModel = $this->getMock('Contao\FilesModel');

        $filesModel->expects($this->any())
            ->method('__get')
            ->will($this->returnCallback(function ($key) {
                return [
                    'importantPartX' => '50',
                    'importantPartY' => '50',
                    'importantPartWidth' => '25',
                    'importantPartHeight' => '25',
                ][$key];
            }));

        $filesAdapter = $this->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->disableOriginalConstructor()
            ->getMock();

        $filesAdapter->expects($this->any())
            ->method('__call')
            ->willReturn($filesModel);

        $framework->expects($this->any())
            ->method('getAdapter')
            ->willReturn($filesAdapter);

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine, null, $framework);

        $image = $imageFactory->create($path, [50, 50, $mode]);

        $this->assertSame($imageMock, $image);
    }

    /**
     * Provides the data for the testCreateWithLegacyMode() method.
     *
     * @return array The data
     */
    public function getCreateWithLegacyMode()
    {
        return [
            'Left Top'      => ['left_top',      [ 0,  0,   1,   1]],
            'Left Center'   => ['left_center',   [ 0,  0,   1, 100]],
            'Left Bottom'   => ['left_bottom',   [ 0, 99,   1,   1]],
            'Center Top'    => ['center_top',    [ 0,  0, 100,   1]],
            'Center Center' => ['center_center', [ 0,  0, 100, 100]],
            'Center Bottom' => ['center_bottom', [ 0, 99, 100,   1]],
            'Right Top'     => ['right_top',     [99,  0,   1,   1]],
            'Right Center'  => ['right_center',  [99,  0,   1, 100]],
            'Right Bottom'  => ['right_bottom',  [99, 99,   1,   1]],
            'Invalid'       => ['top_left',      [ 0,  0, 100, 100]],
        ];
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

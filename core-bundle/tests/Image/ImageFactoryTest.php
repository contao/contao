<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Image;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\Image\Image;
use Contao\Image\ImageDimensionsInterface;
use Contao\Image\ImageInterface;
use Contao\Image\ImportantPart;
use Contao\Image\ImportantPartInterface;
use Contao\Image\ResizeCalculator;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeOptions;
use Contao\Image\Resizer;
use Contao\Image\ResizerInterface;
use Contao\ImageSizeModel;
use Contao\System;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the ImageFactory class.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageFactoryTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        if (file_exists($this->getRootDir().'/assets/images')) {
            (new Filesystem())->remove($this->getRootDir().'/assets/images');
        }
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $imageFactory = $this->createImageFactory();

        $this->assertInstanceOf('Contao\CoreBundle\Image\ImageFactory', $imageFactory);
        $this->assertInstanceOf('Contao\CoreBundle\Image\ImageFactoryInterface', $imageFactory);
    }

    /**
     * Tests the create() method.
     */
    public function testCreate()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';

        $imageMock = $this->createMock(ImageInterface::class);
        $resizer = $this->createMock(ResizerInterface::class);

        $resizer
            ->expects($this->exactly(2))
            ->method('resize')
            ->with(
                $this->callback(
                    function (Image $image) use (&$path) {
                        $this->assertSame($path, $image->getPath());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config) {
                        $this->assertSame(100, $config->getWidth());
                        $this->assertSame(200, $config->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_BOX, $config->getMode());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $filesModel = $this->createMock(FilesModel::class);

        $filesModel
            ->method('__get')
            ->willReturn(null)
        ;

        $filesAdapter = $this->createMock(Adapter::class);

        $filesAdapter
            ->method('__call')
            ->willReturn($filesModel)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturn($filesAdapter)
        ;

        $imageFactory = $this->createImageFactory($resizer, null, null, null, $framework);
        $image = $imageFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSameImage($imageMock, $image);

        $path = $this->getRootDir().'/assets/images/dummy.svg';

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, '');

        $image = $imageFactory->create($path, [100, 200, ResizeConfiguration::MODE_BOX]);

        $this->assertSameImage($imageMock, $image);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateInvalidExtension()
    {
        $imageFactory = $this->createImageFactory();

        $this->expectException('InvalidArgumentException');

        $imageFactory->create($this->getRootDir().'/images/dummy.foo');
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithImageSize()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';

        $imageMock = $this->createMock(ImageInterface::class);
        $resizer = $this->createMock(ResizerInterface::class);

        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(
                    function (Image $image) use ($path) {
                        $this->assertSame($path, $image->getPath());

                        $this->assertSameImportantPart(
                            new ImportantPart(new Point(50, 50), new Box(25, 25)),
                            $image->getImportantPart()
                        );

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config) {
                        $this->assertSame(100, $config->getWidth());
                        $this->assertSame(200, $config->getHeight());
                        $this->assertSame(ResizeConfiguration::MODE_BOX, $config->getMode());
                        $this->assertSame(50, $config->getZoomLevel());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeOptions $options) {
                        $this->assertSame([
                            'jpeg_quality' => 80,
                            'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                        ], $options->getImagineOptions());

                        $this->assertSame($this->getRootDir().'/target/path.jpg', $options->getTargetPath());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);
        $imageSizeModel = $this->createMock(ImageSizeModel::class);

        $imageSizeModel
            ->method('__get')
            ->will(
                $this->returnCallback(function ($key) {
                    return [
                        'width' => '100',
                        'height' => '200',
                        'resizeMode' => ResizeConfiguration::MODE_BOX,
                        'zoom' => '50',
                    ][$key];
                })
            )
        ;

        $imageSizeAdapter = $this->createMock(Adapter::class);

        $imageSizeAdapter
            ->method('__call')
            ->willReturn($imageSizeModel)
        ;

        $filesModel = $this->createMock(FilesModel::class);

        $filesModel
            ->method('__get')
            ->will(
                $this->returnCallback(function ($key) {
                    return [
                        'importantPartX' => '50',
                        'importantPartY' => '50',
                        'importantPartWidth' => '25',
                        'importantPartHeight' => '25',
                    ][$key];
                })
            )
        ;

        $filesAdapter = $this->createMock(Adapter::class);

        $filesAdapter
            ->method('__call')
            ->willReturn($filesModel)
        ;

        $framework
            ->method('getAdapter')
            ->will(
                $this->returnCallback(function ($key) use ($imageSizeAdapter, $filesAdapter) {
                    return [
                        ImageSizeModel::class => $imageSizeAdapter,
                        FilesModel::class => $filesAdapter,
                    ][$key];
                })
            )
        ;

        $imageFactory = $this->createImageFactory($resizer, null, null, null, $framework);
        $image = $imageFactory->create($path, 1, $this->getRootDir().'/target/path.jpg');

        $this->assertSame($imageMock, $image);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithMissingImageSize()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';
        $imageSizeAdapter = $this->createMock(Adapter::class);

        $imageSizeAdapter
            ->method('__call')
            ->willReturn(null)
        ;

        $filesAdapter = $this->createMock(Adapter::class);

        $filesAdapter
            ->method('__call')
            ->willReturn(null)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->will(
                $this->returnCallback(function ($key) use ($imageSizeAdapter, $filesAdapter) {
                    return [
                        ImageSizeModel::class => $imageSizeAdapter,
                        FilesModel::class => $filesAdapter,
                    ][$key];
                })
            )
        ;

        $imageFactory = $this->createImageFactory(null, null, null, null, $framework);
        $image = $imageFactory->create($path, 1);

        $this->assertSame($path, $image->getPath());
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithImageObjectAndResizeConfiguration()
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
                    function ($image) use ($imageMock) {
                        $this->assertSameImage($imageMock, $image);

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfigurationInterface $config) use ($resizeConfig) {
                        $this->assertSame($resizeConfig->isEmpty(), $config->isEmpty());
                        $this->assertSame($resizeConfig->getWidth(), $config->getWidth());
                        $this->assertSame($resizeConfig->getHeight(), $config->getHeight());
                        $this->assertSame($resizeConfig->getMode(), $config->getMode());
                        $this->assertSame($resizeConfig->getZoomLevel(), $config->getZoomLevel());

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeOptions $options) {
                        $this->assertSame([
                            'jpeg_quality' => 80,
                            'interlace' => ImagineImageInterface::INTERLACE_PLANE,
                        ], $options->getImagineOptions());

                        $this->assertSame($this->getRootDir().'/target/path.jpg', $options->getTargetPath());

                        return true;
                    }
                )
            )
            ->willReturn($imageMock)
        ;

        $imageFactory = $this->createImageFactory($resizer);
        $image = $imageFactory->create($imageMock, $resizeConfig, $this->getRootDir().'/target/path.jpg');

        $this->assertSameImage($imageMock, $image);
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithImageObjectAndEmptyResizeConfiguration()
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageFactory = $this->createImageFactory();
        $image = $imageFactory->create($imageMock, (new ResizeConfiguration()));

        $this->assertSameImage($imageMock, $image);
    }

    /**
     * Tests the create() method.
     *
     * @param string $mode
     * @param array  $expected
     *
     * @dataProvider getCreateWithLegacyMode
     */
    public function testCreateWithLegacyMode($mode, array $expected)
    {
        $path = $this->getRootDir().'/images/none.jpg';

        $imageMock = $this->createMock(ImageInterface::class);
        $filesystem = $this->createMock(Filesystem::class);

        $filesystem
            ->expects($this->once())
            ->method('exists')
            ->willReturn(true)
        ;

        $resizer = $this->createMock(ResizerInterface::class);

        $resizer
            ->expects($this->once())
            ->method('resize')
            ->with(
                $this->callback(
                    function (Image $image) use ($path, $expected) {
                        $this->assertSame($path, $image->getPath());

                        $this->assertSameImportantPart(
                            new ImportantPart(
                                new Point($expected[0], $expected[1]),
                                new Box($expected[2], $expected[3])
                            ),
                            $image->getImportantPart()
                        );

                        return true;
                    }
                ),
                $this->callback(
                    function (ResizeConfiguration $config) {
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

        $imagineImageMock = $this->createMock(ImagineImageInterface::class);

        $imagineImageMock
            ->expects($this->once())
            ->method('getSize')
            ->willReturn(new Box(100, 100))
        ;

        $imagine = $this->createMock(ImagineInterface::class);

        $imagine
            ->expects($this->once())
            ->method('open')
            ->willReturn($imagineImageMock)
        ;

        $filesModel = $this->createMock(FilesModel::class);

        $filesModel
            ->method('__get')
            ->will(
                $this->returnCallback(function ($key) {
                    return [
                        'importantPartX' => '50',
                        'importantPartY' => '50',
                        'importantPartWidth' => '25',
                        'importantPartHeight' => '25',
                    ][$key];
                })
            )
        ;

        $filesAdapter = $this->createMock(Adapter::class);

        $filesAdapter
            ->method('__call')
            ->willReturn($filesModel)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturn($filesAdapter)
        ;

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine, $filesystem, $framework);
        $image = $imageFactory->create($path, [50, 50, $mode]);

        $this->assertSame($imageMock, $image);
    }

    /**
     * Tests the getImportantPartFromLegacyMode() method.
     *
     * @param string $mode
     * @param string $expected
     *
     * @dataProvider getCreateWithLegacyMode
     */
    public function testGetImportantPartFromLegacyMode($mode, $expected)
    {
        $dimensionsMock = $this->createMock(ImageDimensionsInterface::class);

        $dimensionsMock
            ->method('getSize')
            ->willReturn(new Box(100, 100))
        ;

        $imageMock = $this->createMock(ImageInterface::class);

        $imageMock
            ->method('getDimensions')
            ->willReturn($dimensionsMock)
        ;

        $imageFactory = $this->createImageFactory();

        $this->assertSameImportantPart(
            new ImportantPart(new Point($expected[0], $expected[1]), new Box($expected[2], $expected[3])),
            $imageFactory->getImportantPartFromLegacyMode($imageMock, $mode)
        );
    }

    /**
     * Tests the getImportantPartFromLegacyMode() method throws an exception for invalid resize modes.
     */
    public function testGetImportantPartFromLegacyModeInvalidMode()
    {
        $imageMock = $this->createMock(ImageInterface::class);
        $imageFactory = $this->createImageFactory();

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('not a legacy resize mode');

        $imageFactory->getImportantPartFromLegacyMode($imageMock, 'invalid');
    }

    /**
     * Provides the data for the testCreateWithLegacyMode() method.
     *
     * @return array
     */
    public function getCreateWithLegacyMode()
    {
        return [
            'Left Top' => ['left_top', [0, 0, 1, 1]],
            'Left Center' => ['left_center', [0, 0, 1, 100]],
            'Left Bottom' => ['left_bottom', [0, 99, 1, 1]],
            'Center Top' => ['center_top', [0, 0, 100, 1]],
            'Center Center' => ['center_center', [0, 0, 100, 100]],
            'Center Bottom' => ['center_bottom', [0, 99, 100, 1]],
            'Right Top' => ['right_top', [99, 0, 1, 1]],
            'Right Center' => ['right_center', [99, 0, 1, 100]],
            'Right Bottom' => ['right_bottom', [99, 99, 1, 1]],
            'Invalid' => ['top_left', [0, 0, 100, 100]],
        ];
    }

    /**
     * Tests the create() method.
     */
    public function testCreateWithoutResize()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturn($this->createMock(Adapter::class))
        ;

        $imageFactory = $this->createImageFactory(null, null, null, null, $framework);
        $image = $imageFactory->create($path);

        $this->assertSame($path, $image->getPath());
    }

    /**
     * Tests the create() method.
     */
    public function testCreateImportantPartOutOfBounds()
    {
        $path = $this->getRootDir().'/images/dummy.jpg';
        $filesModel = $this->createMock(FilesModel::class);

        $filesModel
            ->method('__get')
            ->will(
                $this->returnCallback(function ($key) {
                    return [
                        'importantPartX' => '50',
                        'importantPartY' => '50',
                        'importantPartWidth' => '175',
                        'importantPartHeight' => '175',
                    ][$key];
                })
            )
        ;

        $filesAdapter = $this->createMock(Adapter::class);

        $filesAdapter
            ->method('__call')
            ->willReturn($filesModel)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturn($filesAdapter)
        ;

        $imageFactory = $this->createImageFactory(null, null, null, null, $framework);
        $image = $imageFactory->create($path);

        $this->assertSameImportantPart(
            new ImportantPart(new Point(0, 0), new Box(200, 200)),
            $image->getImportantPart()
        );
    }

    /**
     * Tests the executeResize hook.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExecuteResizeHook()
    {
        define('TL_ROOT', $this->getRootDir());
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpg';

        $path = $this->getRootDir().'/images/dummy.jpg';

        $resizer = new LegacyResizer(
            $this->getRootDir().'/assets/images',
            new ResizeCalculator()
        );

        $imagine = new Imagine();
        $filesAdapter = $this->createMock(Adapter::class);

        $filesAdapter
            ->method('__call')
            ->willReturn($this->createMock(FilesModel::class))
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturn($filesAdapter)
        ;

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine, null, $framework);

        $GLOBALS['TL_HOOKS'] = [
            'executeResize' => [[get_class($this), 'executeResizeHookCallback']],
        ];

        $image = $imageFactory->create($path, [100, 100, ResizeConfiguration::MODE_CROP]);

        $this->assertSame(
            $this->getRootDir().'/assets/images/dummy.jpg&executeResize_100_100_crop__Contao-Image.jpg',
            $image->getPath()
        );

        $image = $imageFactory->create($path, [200, 200, ResizeConfiguration::MODE_CROP]);

        $this->assertSame(
            $this->getRootDir().'/assets/images/dummy.jpg&executeResize_200_200_crop__Contao-Image.jpg',
            $image->getPath()
        );

        $image = $imageFactory->create(
            $path,
            [200, 200, ResizeConfiguration::MODE_CROP],
            $this->getRootDir().'/target.jpg'
        );

        $this->assertSame(
            $this->getRootDir().'/assets/images/dummy.jpg&executeResize_200_200_crop_target.jpg_Contao-Image.jpg',
            $image->getPath()
        );

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * Returns a custom image path.
     *
     * @param object $imageObj The image object
     *
     * @return string The image path
     */
    public static function executeResizeHookCallback($imageObj)
    {
        // Do not include $cacheName as it is dynamic (mtime)
        $path = 'assets/'
            .$imageObj->getOriginalPath()
            .'&executeResize'
            .'_'.$imageObj->getTargetWidth()
            .'_'.$imageObj->getTargetHeight()
            .'_'.$imageObj->getResizeMode()
            .'_'.$imageObj->getTargetPath()
            .'_'.str_replace('\\', '-', get_class($imageObj))
            .'.jpg'
        ;

        if (!file_exists(dirname(TL_ROOT.'/'.$path))) {
            mkdir(dirname(TL_ROOT.'/'.$path), 0777, true);
        }

        file_put_contents(TL_ROOT.'/'.$path, '');

        return $path;
    }

    /**
     * Tests the getImage hook.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetImageHook()
    {
        define('TL_ROOT', $this->getRootDir());
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpg';

        System::setContainer($this->mockContainerWithContaoScopes());

        $path = $this->getRootDir().'/images/dummy.jpg';

        $resizer = new LegacyResizer(
            $this->getRootDir().'/assets/images',
            new ResizeCalculator()
        );

        $imagine = new Imagine();
        $filesAdapter = $this->createMock(Adapter::class);

        $filesAdapter
            ->method('__call')
            ->willReturn($this->createMock(FilesModel::class))
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturn($filesAdapter)
        ;

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine, null, $framework);

        $GLOBALS['TL_HOOKS'] = [
            'executeResize' => [[get_class($this), 'executeResizeHookCallback']],
        ];

        // Build cache before adding the hook
        $imageFactory->create($path, [50, 50, ResizeConfiguration::MODE_CROP]);

        $GLOBALS['TL_HOOKS'] = [
            'getImage' => [[get_class($this), 'getImageHookCallback']],
        ];

        $image = $imageFactory->create($path, [100, 100, ResizeConfiguration::MODE_CROP]);

        $this->assertSame(
            $this->getRootDir().'/assets/images/dummy.jpg&getImage_100_100_crop_Contao-File__Contao-Image.jpg',
            $image->getPath()
        );

        $image = $imageFactory->create($path, [50, 50, ResizeConfiguration::MODE_CROP]);

        $this->assertRegExp(
            '(/images/.*dummy.*.jpg$)',
            $image->getPath(),
            'Hook should not get called for cached images'
        );

        $image = $imageFactory->create($path, [200, 200, ResizeConfiguration::MODE_CROP]);

        $this->assertSame(
            $this->getRootDir().'/images/dummy.jpg',
            $image->getPath(),
            'Hook should not get called if no resize is necessary'
        );

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * Returns a custom image path.
     *
     * @param string $originalPath The original path
     * @param int    $targetWidth  The target width
     * @param int    $targetHeight The target height
     * @param string $resizeMode   The resize mode
     * @param string $cacheName    The cache name
     * @param object $fileObj      The file object
     * @param string $targetPath   The target path
     * @param object $imageObj     The image object
     *
     * @return string The image path
     */
    public static function getImageHookCallback($originalPath, $targetWidth, $targetHeight, $resizeMode, $cacheName, $fileObj, $targetPath, $imageObj)
    {
        // Do not include $cacheName as it is dynamic (mtime)
        $path = 'assets/'
            .$originalPath
            .'&getImage'
            .'_'.$targetWidth
            .'_'.$targetHeight
            .'_'.$resizeMode
            .'_'.str_replace('\\', '-', get_class($fileObj))
            .'_'.$targetPath
            .'_'.str_replace('\\', '-', get_class($imageObj))
            .'.jpg'
        ;

        if (!file_exists(dirname(TL_ROOT.'/'.$path))) {
            mkdir(dirname(TL_ROOT.'/'.$path), 0777, true);
        }

        file_put_contents(TL_ROOT.'/'.$path, '');

        return $path;
    }

    /**
     * Tests empty getImage and executeResize hooks.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEmptyHooks()
    {
        define('TL_ROOT', $this->getRootDir());
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpg';

        System::setContainer($this->mockContainerWithContaoScopes());

        $path = $this->getRootDir().'/images/dummy.jpg';

        $resizer = new LegacyResizer(
            $this->getRootDir().'/assets/images',
            new ResizeCalculator()
        );

        $imagine = new Imagine();
        $filesAdapter = $this->createMock(Adapter::class);

        $filesAdapter
            ->method('__call')
            ->willReturn($this->createMock(FilesModel::class))
        ;

        $configAdapter = $this->createMock(Adapter::class);

        $configAdapter
            ->method('__call')
            ->willReturn(3000)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->will(
                $this->returnCallback(function ($key) use ($filesAdapter, $configAdapter) {
                    return [
                        FilesModel::class => $filesAdapter,
                        Config::class => $configAdapter,
                    ][$key];
                })
            )
        ;

        $resizer->setFramework($framework);

        $imageFactory = $this->createImageFactory($resizer, $imagine, $imagine, null, $framework);

        $GLOBALS['TL_HOOKS'] = [
            'executeResize' => [[get_class($this), 'emptyHookCallback']],
        ];

        $GLOBALS['TL_HOOKS'] = [
            'getImage' => [[get_class($this), 'emptyHookCallback']],
        ];

        $image = $imageFactory->create($path, [100, 100, ResizeConfiguration::MODE_CROP]);

        $this->assertRegExp('(/images/.*dummy.*.jpg$)', $image->getPath(), 'Empty hook should be ignored');
        $this->assertSame(100, $image->getDimensions()->getSize()->getWidth());
        $this->assertSame(100, $image->getDimensions()->getSize()->getHeight());

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * Returns null.
     */
    public static function emptyHookCallback()
    {
        return null;
    }

    /**
     * Create an ImageFactory instance helper.
     *
     * @param LegacyResizer|null            $resizer
     * @param ImagineInterface|null         $imagine
     * @param ImagineInterface|null         $imagineSvg
     * @param Filesystem|null               $filesystem
     * @param ContaoFrameworkInterface|null $framework
     * @param bool                          $bypassCache
     * @param array                         $imagineOptions
     * @param string                        $validExtensions
     *
     * @return ImageFactory
     */
    private function createImageFactory($resizer = null, $imagine = null, $imagineSvg = null, $filesystem = null, $framework = null, $bypassCache = null, $imagineOptions = null, $validExtensions = null)
    {
        if (null === $resizer) {
            $resizer = $this->createMock(ResizerInterface::class);
        }

        if (null === $imagine) {
            $imagine = $this->createMock(ImagineInterface::class);
        }

        if (null === $imagineSvg) {
            $imagineSvg = $this->createMock(ImagineInterface::class);
        }

        if (null === $filesystem) {
            $filesystem = new Filesystem();
        }

        if (null === $framework) {
            $framework = $this->createMock(ContaoFrameworkInterface::class);
        }

        if (null === $bypassCache) {
            $bypassCache = false;
        }

        if (null === $imagineOptions) {
            $imagineOptions = [
                'jpeg_quality' => 80,
                'interlace' => ImagineImageInterface::INTERLACE_PLANE,
            ];
        }

        if (null === $validExtensions) {
            $validExtensions = ['jpg', 'svg'];
        }

        return new ImageFactory(
            $resizer,
            $imagine,
            $imagineSvg,
            $filesystem,
            $framework,
            $bypassCache,
            $imagineOptions,
            $validExtensions
        );
    }

    /**
     * Asserts that two image objects are the same.
     *
     * @param ImageInterface $imageA
     * @param ImageInterface $imageB
     */
    private function assertSameImage(ImageInterface $imageA, ImageInterface $imageB)
    {
        $this->assertSame($imageA->getDimensions(), $imageB->getDimensions());
        $this->assertSame($imageA->getImagine(), $imageB->getImagine());
        $this->assertSame($imageA->getImportantPart(), $imageB->getImportantPart());
        $this->assertSame($imageA->getPath(), $imageB->getPath());
        $this->assertSame($imageA->getUrl($this->getRootDir()), $imageB->getUrl($this->getRootDir()));
    }

    /**
     * Asserts that two important path objects are the same.
     *
     * @param ImportantPartInterface $partA
     * @param ImportantPartInterface $partB
     */
    private function assertSameImportantPart(ImportantPartInterface $partA, ImportantPartInterface $partB)
    {
        $this->assertSame($partA->getPosition()->getX(), $partB->getPosition()->getX());
        $this->assertSame($partA->getPosition()->getY(), $partB->getPosition()->getY());
        $this->assertSame($partA->getSize()->getHeight(), $partB->getSize()->getHeight());
        $this->assertSame($partA->getSize()->getWidth(), $partB->getSize()->getWidth());
    }
}

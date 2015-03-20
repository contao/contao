<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Contao;

use Contao\CoreBundle\Test\TestCase;
use Contao\Image;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the Image class.
 *
 * @author Martin Auswöger <https://github.com/ausi>
 * @author Yanick Witschi <https://github.com/Toflar>
 *
 * @runTestsInSeparateProcesses
 */
class ImageTest extends TestCase
{
    private static $rootDir = __DIR__ . '/../../tmp';

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        $fs = new Filesystem();
        $fs->mkdir(self::$rootDir);
        $fs->mkdir(self::$rootDir . '/assets');
        $fs->mkdir(self::$rootDir . '/assets/images');

        foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f'] as $subdir) {
            $fs->mkdir(self::$rootDir . '/assets/images/' . $subdir);
        }

        $fs->mkdir(self::$rootDir . '/system');
        $fs->mkdir(self::$rootDir . '/system/tmp');
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass()
    {
        $fs = new Filesystem();
        $fs->remove(self::$rootDir);
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        copy(__DIR__ . '/../Fixtures/images/dummy.jpg', self::$rootDir . '/dummy.jpg');

        $GLOBALS['TL_CONFIG']['debugMode'] = false;
        $GLOBALS['TL_CONFIG']['gdMaxImgWidth'] = 3000;
        $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] = 3000;
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpeg,jpg,svg,svgz';

        define('TL_ERROR', 'ERROR');
        define('TL_ROOT', self::$rootDir);
    }

    /**
     * Tests the object instantiation.
     */
    public function testConstruct()
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function ($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    default:
                        return null;
                }
            }
        ));

        $this->assertInstanceOf('Contao\Image', new Image($fileMock));
    }

    /**
     * Tests the object instantiation with a non-existent file.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithNonexistentFile()
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(false));

        new Image($fileMock);
    }

    /**
     * Tests the object instantiation with an invalid extension.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testConstructWithInvalidExtension()
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function ($key) {
                switch ($key) {
                    case 'extension':
                        return 'foobar';
                    default:
                        return null;
                }
            }
        ));

        new Image($fileMock);
    }

    /**
     * Tests the deprecated methods of the Image class.
     */
    public function testGetDeprecatedInvalidImages()
    {
        $this->assertNull(Image::get('', 100, 100));
        $this->assertNull(Image::get(0, 100, 100));
        $this->assertNull(Image::get(null, 100, 100));
    }

    /**
     * Tests resizing without an important part.
     *
     * @dataProvider getComputeResizeDataWithoutImportantPart
     */
    public function testComputeResizeWithoutImportantPart($arguments, $expectedResult)
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function ($key) use ($arguments) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    case 'width':
                        return $arguments[2];
                    case 'height':
                        return $arguments[3];
                    default:
                        return null;
                }
            }
        ));

        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[4]);

        $this->assertEquals(
            $expectedResult,
            $imageObj->computeResize()
        );

        $imageObj->setZoomLevel(50);

        $this->assertEquals(
            $expectedResult,
            $imageObj->computeResize(),
            'Zoom 50 should return the same results if no important part is specified'
        );

        $imageObj->setZoomLevel(100);

        $this->assertEquals(
            $expectedResult,
            $imageObj->computeResize(),
            'Zoom 100 should return the same results if no important part is specified'
        );
    }

    /**
     * Provides the data for the testComputeResizeWithoutImportantPart() method.
     *
     * @return array The data
     */
    public function getComputeResizeDataWithoutImportantPart()
    {
        return [
            'No dimensions' => [
                [null, null, 100, 100, null],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Same dimensions' => [
                [100, 100, 100, 100, null],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Scale down' => [
                [50, 50, 100, 100, null],
                [
                    'width' => 50,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 50,
                    'target_height' => 50,
                ],
            ],
            'Scale up' => [
                [100, 100, 50, 50, null],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Width only' => [
                [100, null, 50, 50, null],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Height only' => [
                [null, 100, 50, 50, null],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Crop landscape' => [
                [100, 50, 100, 100, null],
                [
                    'width' => 100,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => -25,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Crop portrait' => [
                [50, 100, 100, 100, null],
                [
                    'width' => 50,
                    'height' => 100,
                    'target_x' => -25,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Mode proportional landscape' => [
                [100, 10, 100, 50, 'proportional'],
                [
                    'width' => 100,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 50,
                ],
            ],
            'Mode proportional portrait' => [
                [10, 100, 50, 100, 'proportional'],
                [
                    'width' => 50,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 50,
                    'target_height' => 100,
                ],
            ],
            'Mode proportional square' => [
                [100, 50, 100, 100, 'proportional'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Mode box landscape 1' => [
                [100, 100, 100, 50, 'box'],
                [
                    'width' => 100,
                    'height' => 50,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 50,
                ],
            ],
            'Mode box landscape 2' => [
                [100, 10, 100, 50, 'box'],
                [
                    'width' => 20,
                    'height' => 10,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 20,
                    'target_height' => 10,
                ],
            ],
            'Mode box portrait 1' => [
                [100, 100, 50, 100, 'box'],
                    [
                    'width' => 50,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 50,
                    'target_height' => 100,
                ],
            ],
            'Mode box portrait 2' => [
                [10, 100, 50, 100, 'box'],
                [
                    'width' => 10,
                    'height' => 20,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 10,
                    'target_height' => 20,
                ],
            ],
            'Mode left_top landscape' => [
                [100, 100, 100, 50, 'left_top'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode left_top portrait' => [
                [100, 100, 50, 100, 'left_top'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode center_top landscape' => [
                [100, 100, 100, 50, 'center_top'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -50,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode center_top portrait' => [
                [100, 100, 50, 100, 'center_top'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode right_top landscape' => [
                [100, 100, 100, 50, 'right_top'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -100,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode right_top portrait' => [
                [100, 100, 50, 100, 'right_top'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode left_center landscape' => [
                [100, 100, 100, 50, 'left_center'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode left_center portrait' => [
                [100, 100, 50, 100, 'left_center'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => -50,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode center_center landscape' => [
                [100, 100, 100, 50, 'center_center'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -50,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode center_center portrait' => [
                [100, 100, 50, 100, 'center_center'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => -50,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode right_center landscape' => [
                [100, 100, 100, 50, 'right_center'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -100,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode right_center portrait' => [
                [100, 100, 50, 100, 'right_center'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => -50,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode left_bottom landscape' => [
                [100, 100, 100, 50, 'left_bottom'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode left_bottom portrait' => [
                [100, 100, 50, 100, 'left_bottom'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => -100,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode center_bottom landscape' => [
                [100, 100, 100, 50, 'center_bottom'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -50,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode center_bottom portrait' => [
                [100, 100, 50, 100, 'center_bottom'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => -100,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Mode right_bottom landscape' => [
                [100, 100, 100, 50, 'right_bottom'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -100,
                    'target_y' => 0,
                    'target_width' => 200,
                    'target_height' => 100,
                ],
            ],
            'Mode right_bottom portrait' => [
                [100, 100, 50, 100, 'right_bottom'],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => -100,
                    'target_width' => 100,
                    'target_height' => 200,
                ],
            ],
            'Float values' => [
                [100.4, 100.4, 50, 50, null],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
        ];
    }

    /**
     * Tests resizing with an important part.
     *
     * @dataProvider getComputeResizeDataWithImportantPart
     */
    public function testComputeResizeWithImportantPart($arguments, $expectedResult)
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function ($key) use ($arguments) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    case 'width':
                        return $arguments[2];
                    case 'height':
                        return $arguments[3];
                    default:
                        return null;
                }
            }
        ));

        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[4]);
        $imageObj->setZoomLevel($arguments[5]);
        $imageObj->setImportantPart($arguments[6]);

        $this->assertEquals(
            $expectedResult,
            $imageObj->computeResize()
        );
    }

    /**
     * Provides the data for the testComputeResizeWithImportantPart() method.
     *
     * @return array The data
     */
    public function getComputeResizeDataWithImportantPart()
    {
        return [

            'No dimensions zoom 0' => [
                [null, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'No dimensions zoom 50' => [
                [null, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 80,
                    'height' => 80,
                    'target_x' => -10,
                    'target_y' => -10,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'No dimensions zoom 100' => [
                [null, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 60,
                    'height' => 60,
                    'target_x' => -20,
                    'target_y' => -20,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Width only zoom 0' => [
                [100, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Width only zoom 50' => [
                [100, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -13,
                    'target_y' => -13,
                    'target_width' => 125,
                    'target_height' => 125,
                ],
            ],
            'Width only zoom 100' => [
                [100, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -33,
                    'target_y' => -33,
                    'target_width' => 167,
                    'target_height' => 167,
                ],
            ],
            'Same dimensions zoom 0' => [
                [100, 100, 100, 100, null, 0, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => 0,
                    'target_y' => 0,
                    'target_width' => 100,
                    'target_height' => 100,
                ],
            ],
            'Same dimensions zoom 50' => [
                [100, 100, 100, 100, null, 50, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -17,
                    'target_y' => -17,
                    'target_width' => 133,
                    'target_height' => 133,
                ],
            ],
            'Same dimensions zoom 100' => [
                [100, 100, 100, 100, null, 100, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
                [
                    'width' => 100,
                    'height' => 100,
                    'target_x' => -50,
                    'target_y' => -50,
                    'target_width' => 200,
                    'target_height' => 200,
                ],
            ],
            'Landscape to portrait zoom 0' => [
                [100, 200, 200, 100, null, 0, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]],
                [
                    'width' => 100,
                    'height' => 200,
                    'target_x' => -233,
                    'target_y' => 0,
                    'target_width' => 400,
                    'target_height' => 200,
                ],
            ],
            'Landscape to portrait zoom 50' => [
                [100, 200, 200, 100, null, 50, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]],
                [
                    'width' => 100,
                    'height' => 200,
                    'target_x' => -367,
                    'target_y' => -43,
                    'target_width' => 571,
                    'target_height' => 286,
                ],
            ],
            'Landscape to portrait zoom 100' => [
                [100, 200, 200, 100, null, 100, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]],
                [
                    'width' => 100,
                    'height' => 200,
                    'target_x' => -700,
                    'target_y' => -150,
                    'target_width' => 1000,
                    'target_height' => 500,
                ],
            ],
        ];
    }

    /**
     * Tests the setters and getters.
     */
    public function testSettersAndGetters()
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function ($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    case 'width':
                        return 100;
                    case 'height':
                        return 100;
                    default:
                        return null;
                }
            }
        ));

        $imageObj = new Image($fileMock);

        $this->assertFalse($imageObj->getForceOverride());
        $imageObj->setForceOverride(true);
        $this->assertTrue($imageObj->getForceOverride());

        $this->assertSame($imageObj->getImportantPart(), [
            'x' => 0,
            'y' => 0,
            'width' => 100,
            'height' => 100,
        ]);

        $imageObj->setImportantPart([
            'x' => 20,
            'y' => 40,
            'width' => 80,
            'height' => 120,
        ]);

        $this->assertSame($imageObj->getImportantPart(), [
            'x' => 20,
            'y' => 40,
            'width' => 80,
            'height' => 120,
        ]);

        $this->assertSame($imageObj->getTargetHeight(), 0);
        $imageObj->setTargetHeight(20);
        $this->assertSame($imageObj->getTargetHeight(), 20);
        $imageObj->setTargetHeight(50.125);
        $this->assertSame($imageObj->getTargetHeight(), 50);

        $this->assertSame($imageObj->getTargetWidth(), 0);
        $imageObj->setTargetWidth(20);
        $this->assertSame($imageObj->getTargetWidth(), 20);
        $imageObj->setTargetWidth(50.125);
        $this->assertSame($imageObj->getTargetWidth(), 50);

        $this->assertSame($imageObj->getTargetPath(), '');
        $imageObj->setTargetPath('foobar');
        $this->assertSame($imageObj->getTargetPath(), 'foobar');

        $this->assertSame($imageObj->getZoomLevel(), 0);
        $imageObj->setZoomLevel(54);
        $this->assertSame($imageObj->getZoomLevel(), 54);

        $this->assertSame($imageObj->getResizeMode(), 'crop');
        $imageObj->setResizeMode('foobar');
        $this->assertSame($imageObj->getResizeMode(), 'foobar');

        $this->assertSame($imageObj->getOriginalPath(), 'dummy.jpg');
        $this->assertSame($imageObj->getResizedPath(), '');
    }

    /**
     * Tests the getCacheName() method.
     *
     * @dataProvider getCacheName
     */
    public function testGetCacheName($arguments, $expectedCacheName)
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function ($key) use ($arguments) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return $arguments[2];
                    case 'filename':
                        return $arguments[2];
                    case 'mtime':
                        return $arguments[5];
                    default:
                        return null;
                }
            }
        ));

        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[3]);
        $imageObj->setZoomLevel($arguments[4]);
        $imageObj->setImportantPart($arguments[6]);

        $this->assertSame($imageObj->getCacheName(), $expectedCacheName);
    }

    /**
     * Provides the data for the testGetCacheName() method.
     *
     * @return array The data
     */
    public function getCacheName()
    {
        // target width, target height, file name (path), resize mode, zoom level, mtime, important part
        // expected cache name
        return [
            [
                [100, 100, 'dummy.jpg', 'crop', 0, 12345678, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                'assets/images/c/dummy.jpg-fc94db8c.jpg',
            ],
            [
                [200, 100, 'test.jpg', 'proportional', 50, 87654321, ['x' => 30, 'y' => 20, 'width' => 60, 'height' => 90]],
                'assets/images/3/test.jpg-4e7b07e3.jpg',
            ],
            [
                [100, 200, 'other.jpg', 'center_center', 100, 6666666, ['x' => 10, 'y' => 20, 'width' => 70, 'height' => 20]],
                'assets/images/f/other.jpg-1fe4f44f.jpg',
            ],
        ];
    }

    /**
     * Tests the setZoomLevel() with a negative out of bounds value.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSetZoomOutOfBoundsNegative()
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function ($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    default:
                        return null;
                }
            }
        ));

        $imageObj = new Image($fileMock);
        $imageObj->setZoomLevel(-1);
    }

    /**
     * Tests the setZoomLevel() method with a positive out of bounds value.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testSetZoomOutOfBoundsPositive()
    {
        $fileMock = $this->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock();

        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function ($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    default:
                        return null;
                }
            }
        ));

        $imageObj = new Image($fileMock);
        $imageObj->setZoomLevel(101);
    }

    /**
     * Tests the legacy get() method.
     *
     * @dataProvider getLegacyGet
     */
    public function testLegacyGet($arguments, $expectedResult)
    {
        $result = Image::get(
            $arguments[0],
            $arguments[1],
            $arguments[2],
            $arguments[3],
            $arguments[4],
            $arguments[5]
        );

        $this->assertSame($result, $expectedResult);
    }

    /**
     * Provides the data for the testLegacyGet() method.
     *
     * @return array The data
     */
    public function getLegacyGet()
    {
        // original image, target width, target height, resize mode, target, force override
        // expected result
        return [
            'No empty image path returns null' => [
                ['', 100, 100, 'crop', null, false],
                null,
            ],
            'Inexistent file returns null' => [
                ['foobar.jpg', 100, 100, 'crop', null, false],
                null,
            ],
            // Currently not testable (see contao/core-bundle#25)
            //'No resize necessary returns same path' => [
            //   ['dummy.jpg', 200, 200, 'crop', null, false],
            //   'dummy.jpg'
            //],
            //'No resize necessary with target path' => [
            //    ['dummy.jpg', 200, 200, 'crop', 'target/path/dummy.jpg', false],
            //    'target/path/dummy.jpg'
            //]
        ];
    }

    /**
     * Tests the legacy resize() method.
     *
     * @dataProvider getLegacyResize
     */
    public function testLegacyResize($arguments, $expectedResult)
    {
        $result = Image::resize(
            $arguments[0],
            $arguments[1],
            $arguments[2],
            $arguments[3]
        );

        $this->assertSame($result, $expectedResult);
    }

    /**
     * Provides the data for the testLegacyGet() method.
     *
     * @return array The data
     */
    public function getLegacyResize()
    {
        // original image, target width, target height, resize mode
        // expected result
        return [
            'No empty image path returns false' => [
                ['', 100, 100, 'crop'],
                false,
            ],
            'Inexistent file returns false' => [
                ['foobar.jpg', 100, 100, 'crop'],
                false,
            ],
            // Currently not testable (see contao/core-bundle#25)
            //'No resize necessary returns true' => [
            //    ['dummy.jpg', 200, 200, 'crop', null, false],
            //    true
            //]
        ];
    }

    /**
     * Tests resizing an image which already matches the given dimensions.
     */
    public function testExecuteResizeNoResizeNeeded()
    {
        $file = new \File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(200)->setTargetHeight(200);
        $imageObj->executeResize();

        $resultFile = new \File($imageObj->getResizedPath());

        $this->assertSame($resultFile->width, 200);
        $this->assertSame($resultFile->height, 200);
    }

    /**
     * Tests resizing an image which has to be cropped.
     */
    public function testExecuteResizeStandardCropResize()
    {
        $file = new \File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new \File($imageObj->getResizedPath());

        $this->assertSame($resultFile->width, 100);
        $this->assertSame($resultFile->height, 100);
    }

    /**
     * Tests resizing an image which has to be cropped and has a target defined.
     */
    public function testExecuteResizeStandardCropResizeAndTarget()
    {
        $file = new \File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100)->setTargetPath('dummy_foobar.jpg');
        $imageObj->executeResize();

        $resultFile = new \File($imageObj->getResizedPath());

        $this->assertSame($resultFile->width, 100);
        $this->assertSame($resultFile->height, 100);
        $this->assertSame($resultFile->path, 'dummy_foobar.jpg');
    }

    /**
     * Tests resizing an image which has to be cropped and has an existing target defined.
     */
    public function testExecuteResizeStandardCropResizeAndFileExistsAlready()
    {
        $file = new \File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100)->setTargetPath('dummy_foobar.jpg');
        $imageObj->executeResize();

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new \File($imageObj->getResizedPath());

        $this->assertSame($resultFile->width, 100);
        $this->assertSame($resultFile->height, 100);
    }

    /**
     * Tests resizing an SVG image.
     */
    public function testExecuteResizeSvg()
    {
        file_put_contents(
            self::$rootDir . '/dummy.svg',
            '<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
            <svg
                version="1.1"
                xmlns="http://www.w3.org/2000/svg"
                width="200px"
                height="100px"
                viewBox="100 100 400 200"
            ></svg>'
        );

        $file = new \File('dummy.svg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new \File($imageObj->getResizedPath());

        $this->assertEquals(100, $resultFile->width);
        $this->assertEquals(100, $resultFile->height);

        $doc = new \DOMDocument();
        $doc->loadXML($resultFile->getContent());

        $this->assertEquals('100 100 400 200', $doc->documentElement->firstChild->getAttribute('viewBox'));
        $this->assertEquals('-50', $doc->documentElement->firstChild->getAttribute('x'));
        $this->assertEquals('0', $doc->documentElement->firstChild->getAttribute('y'));
        $this->assertEquals('200', $doc->documentElement->firstChild->getAttribute('width'));
        $this->assertEquals('100', $doc->documentElement->firstChild->getAttribute('height'));
    }

    /**
     * Tests resizing an SVGZ image.
     */
    public function testExecuteResizeSvgz()
    {
        file_put_contents(
            self::$rootDir . '/dummy.svgz',
            gzencode(
                '<?xml version="1.0" encoding="utf-8"?>
                <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
                <svg
                    version="1.1"
                    xmlns="http://www.w3.org/2000/svg"
                    width="200px"
                    height="100px"
                    viewBox="100 100 400 200"
                ></svg>'
            )
        );

        $file = new \File('dummy.svgz');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new \File($imageObj->getResizedPath());
        $this->assertEquals(100, $resultFile->width);
        $this->assertEquals(100, $resultFile->height);

        $doc = new \DOMDocument();
        $doc->loadXML(gzdecode($resultFile->getContent()));

        $this->assertEquals('100 100 400 200', $doc->documentElement->firstChild->getAttribute('viewBox'));
        $this->assertEquals('-50', $doc->documentElement->firstChild->getAttribute('x'));
        $this->assertEquals('0', $doc->documentElement->firstChild->getAttribute('y'));
        $this->assertEquals('200', $doc->documentElement->firstChild->getAttribute('width'));
        $this->assertEquals('100', $doc->documentElement->firstChild->getAttribute('height'));
    }

    /**
     * Tests the getImage hook.
     */
    public function testExecuteResizeHook()
    {
        $GLOBALS['TL_HOOKS']['getImage'][] = [get_class($this), 'getImageHookCallback'];

        $file = new \File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $this->assertSame($imageObj->getResizedPath(), 'dummy.jpg%3B100%3B100%3Bcrop%3BContao%5CFile%3B%3BContao%5CImage');
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
        return $originalPath . ';' . $targetWidth . ';' . $targetHeight . ';' . $resizeMode . ';' . get_class($fileObj) . ';' . $targetPath . ';' . get_class($imageObj);
    }

    /**
     * Tests the getPixelValue() method.
     *
     * @dataProvider getGetPixelValueData
     */
    public function testGetPixelValue($value, $expected)
    {
        $this->assertSame($expected, Image::getPixelValue($value));
    }

    /**
     * Provides the data for the testGetPixelValue() method.
     *
     * @return array The data
     */
    public function getGetPixelValueData()
    {
        return [
            'No unit' => ['1234.5', 1235],
            'px'      => ['1234.5px', 1235],
            'em'      => ['1em', 16],
            'pt'      => ['12pt', 16],
            'percent' => ['100%', 16],
            'invalid' => ['abc', 0],
        ];
    }
}

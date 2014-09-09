<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Library
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\Test;

use ReflectionClass;
use Contao\Image;

/**
 * @runTestsInSeparateProcesses
 */
class ImageTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpeg,jpg';
        class_alias('Contao\File', 'File');
        class_alias('Contao\Files', 'Files');
        class_alias('Contao\System', 'System');
        define('TL_ERROR', 'ERROR');
        define('TL_ROOT', __DIR__);

        parent::setUp();
    }

    private static function callProtectedStatic($class, $method, $arguments = [])
    {
        $classReflection = new ReflectionClass($class);
        $methodReflection = $classReflection->getMethod($method);
        $methodReflection->setAccessible(true);
        return $methodReflection->invokeArgs(null, $arguments);
    }

    public function testGetDeprecatedInvalidImages()
    {
        $this->assertNull(Image::get('', 100, 100));
        $this->assertNull(Image::get(0, 100, 100));
        $this->assertNull(Image::get(null, 100, 100));
    }

    /**
     * @dataProvider getComputeResizeDataWithoutImportantPart
     */
    public function testComputeResizeWithoutImportantPart($arguments, $expectedResult)
    {
        $fileMock = $this->getMockBuilder('File')
                    ->setMethods(array('__get', 'exists'))
                    ->setConstructorArgs(array('dummy.jpg'))
                    ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) use($arguments) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    case 'width':
                        return $arguments[2];
                    case 'height':
                        return $arguments[3];
                }
            }
        ));


        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[4]);
        $imageObj->setZoomLevel(0);
        $imageObj->setImportantPart([
            'x' => 0,
            'y' => 0,
            'width' => $arguments[2],
            'height' => $arguments[3],
        ]);

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

    public function getComputeResizeDataWithoutImportantPart()
    {
        return [

            'No dimensions' =>
            [[null, null, 100, 100, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Same dimensions' =>
            [[100, 100, 100, 100, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Scale down' =>
            [[50, 50, 100, 100, null], [
                'width' => 50,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 50,
            ]],

            'Scale up' =>
            [[100, 100, 50, 50, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Width only' =>
            [[100, null, 50, 50, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Height only' =>
            [[null, 100, 50, 50, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Crop landscape' =>
            [[100, 50, 100, 100, null], [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => -25,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Crop portrait' =>
            [[50, 100, 100, 100, null], [
                'width' => 50,
                'height' => 100,
                'target_x' => -25,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Mode proportional landscape' =>
            [[100, 10, 100, 50, 'proportional'], [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 50,
            ]],

            'Mode proportional portrait' =>
            [[10, 100, 50, 100, 'proportional'], [
                'width' => 50,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 100,
            ]],

            'Mode proportional square' =>
            [[100, 50, 100, 100, 'proportional'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Mode box landscape 1' =>
            [[100, 100, 100, 50, 'box'], [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 50,
            ]],

            'Mode box landscape 2' =>
            [[100, 10, 100, 50, 'box'], [
                'width' => 20,
                'height' => 10,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 20,
                'target_height' => 10,
            ]],

            'Mode box portrait 1' =>
            [[100, 100, 50, 100, 'box'], [
                'width' => 50,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 100,
            ]],

            'Mode box portrait 2' =>
            [[10, 100, 50, 100, 'box'], [
                'width' => 10,
                'height' => 20,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 10,
                'target_height' => 20,
            ]],

            'Mode left_top landscape' =>
            [[100, 100, 100, 50, 'left_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode left_top portrait' =>
            [[100, 100, 50, 100, 'left_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode center_top landscape' =>
            [[100, 100, 100, 50, 'center_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode center_top portrait' =>
            [[100, 100, 50, 100, 'center_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode right_top landscape' =>
            [[100, 100, 100, 50, 'right_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode right_top portrait' =>
            [[100, 100, 50, 100, 'right_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode left_center landscape' =>
            [[100, 100, 100, 50, 'left_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode left_center portrait' =>
            [[100, 100, 50, 100, 'left_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode center_center landscape' =>
            [[100, 100, 100, 50, 'center_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode center_center portrait' =>
            [[100, 100, 50, 100, 'center_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode right_center landscape' =>
            [[100, 100, 100, 50, 'right_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode right_center portrait' =>
            [[100, 100, 50, 100, 'right_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode left_bottom landscape' =>
            [[100, 100, 100, 50, 'left_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode left_bottom portrait' =>
            [[100, 100, 50, 100, 'left_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode center_bottom landscape' =>
            [[100, 100, 100, 50, 'center_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode center_bottom portrait' =>
            [[100, 100, 50, 100, 'center_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode right_bottom landscape' =>
            [[100, 100, 100, 50, 'right_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode right_bottom portrait' =>
            [[100, 100, 50, 100, 'right_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Float values' =>
            [[100.4, 100.4, 50, 50, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],
        ];
    }

    /**
     * @dataProvider getComputeResizeDataWithImportantPart
     */
    public function testComputeResizeWithImportantPart($arguments, $expectedResult)
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) use($arguments) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    case 'width':
                        return $arguments[2];
                    case 'height':
                        return $arguments[3];
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

    public function getComputeResizeDataWithImportantPart()
    {
        return [

            'No dimensions zoom 0' =>
            [[null, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'No dimensions zoom 50' =>
            [[null, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 80,
                'height' => 80,
                'target_x' => -10,
                'target_y' => -10,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'No dimensions zoom 100' =>
            [[null, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 60,
                'height' => 60,
                'target_x' => -20,
                'target_y' => -20,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Width only zoom 0' =>
            [[100, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Width only zoom 50' =>
            [[100, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 100,
                'height' => 100,
                'target_x' => -13,
                'target_y' => -13,
                'target_width' => 125,
                'target_height' => 125,
            ]],

            'Width only zoom 100' =>
            [[100, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 100,
                'height' => 100,
                'target_x' => -33,
                'target_y' => -33,
                'target_width' => 167,
                'target_height' => 167,
            ]],

            'Same dimensions zoom 0' =>
            [[100, 100, 100, 100, null, 0, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Same dimensions zoom 50' =>
            [[100, 100, 100, 100, null, 50, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]], [
                'width' => 100,
                'height' => 100,
                'target_x' => -17,
                'target_y' => -17,
                'target_width' => 133,
                'target_height' => 133,
            ]],

            'Same dimensions zoom 100' =>
            [[100, 100, 100, 100, null, 100, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]], [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => -50,
                'target_width' => 200,
                'target_height' => 200,
            ]],

            'Landscape to portrait zoom 0' =>
            [[100, 200, 200, 100, null, 0, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]], [
                'width' => 100,
                'height' => 200,
                'target_x' => -233,
                'target_y' => 0,
                'target_width' => 400,
                'target_height' => 200,
            ]],

            'Landscape to portrait zoom 50' =>
            [[100, 200, 200, 100, null, 50, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]], [
                'width' => 100,
                'height' => 200,
                'target_x' => -367,
                'target_y' => -43,
                'target_width' => 571,
                'target_height' => 286,
            ]],

            'Landscape to portrait zoom 100' =>
            [[100, 200, 200, 100, null, 100, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]], [
                'width' => 100,
                'height' => 200,
                'target_x' => -700,
                'target_y' => -150,
                'target_width' => 1000,
                'target_height' => 500,
            ]]

        ];
    }

    public function testCreateGdImage()
    {
        $image = self::callProtectedStatic('Contao\\Image', 'createGdImage', [100, 100]);

        $this->assertInternalType('resource', $image);
        $this->assertTrue(imageistruecolor($image));
        $this->assertEquals(100, imagesx($image));
        $this->assertEquals(100, imagesy($image));
    }

    /**
     * @expectedException   InvalidArgumentException
     */
    public function testSetZoomOutOfBoundsNegative()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $imageObj = new Image($fileMock);
        $imageObj->setZoomLevel(-1);
    }

    /**
     * @expectedException   InvalidArgumentException
     */
    public function testSetZoomOutOfBoundsPositive()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));

        $imageObj = new Image($fileMock);
        $imageObj->setZoomLevel(101);
    }
}

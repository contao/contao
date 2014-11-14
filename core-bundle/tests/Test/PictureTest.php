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

use Contao\File;
use Contao\Picture;

/**
 * @runTestsInSeparateProcesses
 */
class PictureTest extends \PHPUnit_Framework_TestCase
{
    var $tempDirectory;

    protected function setUp()
    {
        $this->tempDirectory = __DIR__ . '/../tmp';
        mkdir($this->tempDirectory);
        mkdir($this->tempDirectory . '/assets');
        mkdir($this->tempDirectory . '/assets/images');
        mkdir($this->tempDirectory . '/system');
        mkdir($this->tempDirectory . '/system/tmp');
        foreach ([0,1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f'] as $subdir) {
            mkdir($this->tempDirectory . '/assets/images/' . $subdir);
        }

        copy(__DIR__ . '/../Fixtures/dummy.jpg', $this->tempDirectory . '/dummy.jpg');

        eval('class SystemTest extends Contao\System
        {
            public static function log($strText, $strFunction, $strCategory) {}
        }');


        $GLOBALS['TL_CONFIG']['debugMode'] = false;
        $GLOBALS['TL_CONFIG']['gdMaxImgWidth'] = 3000;
        $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] = 3000;
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpeg,jpg,svg,svgz';
        class_alias('SystemTest', 'System');
        class_alias('Contao\Image', 'Image');
        class_alias('Contao\GdImage', 'GdImage');
        class_alias('Contao\File', 'File');
        class_alias('Contao\Files', 'Files');
        class_alias('Contao\Config', 'Config');
        define('TL_ERROR', 'ERROR');
        define('TL_ROOT', $this->tempDirectory);
        define('TL_FILES_URL', '');

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        // Delete temp directory
        exec('rm -rf ' . escapeshellarg($this->tempDirectory));
    }

    public function testConstruct()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                }
            }
        ));

        $this->assertInstanceOf('Contao\Picture', new Picture($fileMock));
    }

    public function testGetTemplateData()
    {
        $picture = new Picture(new \File('dummy.jpg'));
        $picture->setImageSize((object)[
            'width' => 0,
            'height' => 0,
            'resizeMode' => '',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertEquals(200, $pictureData['img']['width']);
        $this->assertEquals(200, $pictureData['img']['height']);
        $this->assertEquals('dummy.jpg', $pictureData['img']['src']);
        $this->assertEquals('dummy.jpg', $pictureData['img']['srcset']);
        $this->assertEquals([], $pictureData['sources']);
    }

    public function testGetTemplateDataImgOnly()
    {
        $picture = new Picture(new \File('dummy.jpg'));
        $picture->setImageSize((object)[
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertEquals(100, $pictureData['img']['width']);
        $this->assertEquals(100, $pictureData['img']['height']);
        $this->assertEquals($pictureData['img']['src'], $pictureData['img']['srcset'], 'Attributes src and srcset should be equal');
        $this->assertEquals([], $pictureData['sources']);
    }

    public function testGetTemplateDataWithSources()
    {
        $picture = new Picture(new \File('dummy.jpg'));
        $picture->setImageSize((object)[
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
        ]);
        $picture->setImageSizeItems([
            (object)[
                'width' => 50,
                'height' => 50,
                'resizeMode' => 'crop',
                'zoom' => 0,
                'media' => '(max-width: 900px)',
            ],
            (object)[
                'width' => 25,
                'height' => 25,
                'resizeMode' => 'crop',
                'zoom' => 0,
                'media' => '(max-width: 600px)',
            ],
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertEquals(100, $pictureData['img']['width']);
        $this->assertEquals(100, $pictureData['img']['height']);
        $this->assertEquals($pictureData['img']['src'], $pictureData['img']['srcset'], 'Attributes src and srcset should be equal');
        $this->assertEquals(50, $pictureData['sources'][0]['width']);
        $this->assertEquals(50, $pictureData['sources'][0]['height']);
        $this->assertEquals('(max-width: 900px)', $pictureData['sources'][0]['media']);
        $this->assertEquals($pictureData['sources'][0]['src'], $pictureData['sources'][0]['srcset'], 'Attributes src and srcset should be equal');
        $this->assertEquals(25, $pictureData['sources'][1]['width']);
        $this->assertEquals(25, $pictureData['sources'][1]['height']);
        $this->assertEquals('(max-width: 600px)', $pictureData['sources'][1]['media']);
        $this->assertEquals($pictureData['sources'][1]['src'], $pictureData['sources'][1]['srcset'], 'Attributes src and srcset should be equal');
    }

    public function testGetTemplateDataWithDensities()
    {
        $picture = new Picture(new \File('dummy.jpg'));
        $picture->setImageSize((object)[
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
            'densities' => '0.5x, 2x',
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertEquals(100, $pictureData['img']['width']);
        $this->assertEquals(100, $pictureData['img']['height']);
        $this->assertCount(1, explode(',', $pictureData['img']['src']));
        $this->assertCount(3, explode(',', $pictureData['img']['srcset']));
        $this->assertRegExp('(\\.jpg\\s+1x(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\\.jpg\\s+0\\.5x(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\\.jpg\\s+2x(,|$))', $pictureData['img']['srcset']);
        $this->assertEquals([], $pictureData['sources']);
    }

    public function testGetTemplateDataWithDensitiesSizes()
    {
        $picture = new Picture(new \File('dummy.jpg'));
        $picture->setImageSize((object)[
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
            'densities' => '0.5x, 2x',
            'sizes' => '100vw',
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertEquals(100, $pictureData['img']['width']);
        $this->assertEquals(100, $pictureData['img']['height']);
        $this->assertEquals('100vw', $pictureData['img']['sizes']);
        $this->assertCount(1, explode(',', $pictureData['img']['src']));
        $this->assertCount(3, explode(',', $pictureData['img']['srcset']));
        $this->assertRegExp('(\\.jpg\\s+100w(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\\.jpg\\s+50w(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\\.jpg\\s+200w(,|$))', $pictureData['img']['srcset']);
        $this->assertEquals([], $pictureData['sources']);
    }
}

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
use Contao\GdImage;

/**
 * @runTestsInSeparateProcesses
 */
class GdImageTest extends \PHPUnit_Framework_TestCase
{
    var $tempDirectory;

    protected function setUp()
    {
        $this->tempDirectory = __DIR__ . '/../tmp';
        if (!is_dir($this->tempDirectory)) {
            mkdir($this->tempDirectory);
        }

        class_alias('Contao\File', 'File');
        class_alias('Contao\Files', 'Files');
        class_alias('Contao\Config', 'Config');
        define('TL_ROOT', $this->tempDirectory);

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
        $resource = imagecreate(1, 1);
        $image = new GdImage($resource);

        $this->assertInstanceOf('Contao\GdImage', $image);
        $this->assertSame($resource, $image->getResource());
    }

    public function testFromDimension()
    {
        $image = GdImage::fromDimensions(100, 100);

        $this->assertInternalType('resource', $image->getResource());
        $this->assertTrue(imageistruecolor($image->getResource()));
        $this->assertEquals(100, imagesx($image->getResource()));
        $this->assertEquals(100, imagesy($image->getResource()));
        $this->assertEquals(127, imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 0, 0))["alpha"], 'Image should be transparent');
        $this->assertEquals(127, imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 99, 99))["alpha"], 'Image should be transparent');
    }

    public function testFromFile()
    {
        foreach (['gif', 'jpeg', 'png'] as $type) {
            $image = imagecreatetruecolor(100, 100);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));
            $method = 'image' . $type;
            $method($image, $this->tempDirectory . '/test.' . $type);
            imagedestroy($image);

            $image = GdImage::fromFile(new \File('test.' . $type));

            $this->assertInternalType('resource', $image->getResource());
            $this->assertEquals(100, imagesx($image->getResource()));
            $this->assertEquals(100, imagesy($image->getResource()));
        }
    }

    public function testFromFileInvalidType()
    {
        $this->setExpectedException('InvalidArgumentException');

        GdImage::fromFile(new \File('test.xyz'));
    }

    public function testSaveToFile()
    {
        foreach (['gif', 'jpeg', 'png'] as $type) {
            $file = $this->tempDirectory . '/test.' . $type;
            $image = GdImage::fromDimensions(100, 100);

            $image->saveToFile($file);

            $this->assertFileExists($file);

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $this->assertEquals('image/' . $type, $finfo->file($file));
        }
    }

    public function testCopyTo()
    {
        $image = imagecreatetruecolor(100, 100);

        // Whole image black
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));

        $image = new GdImage($image);

        $target = GdImage::fromDimensions(100, 100);

        $image->copyTo($target, 10, 10, 80, 80);

        $this->assertEquals(
            ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0],
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 50, 50)),
            'Center should be black'
        );

        $this->assertEquals(
            ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0],
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 10, 10)),
            '10 pixel from left top should be black'
        );

        $this->assertEquals(
            ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0],
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 89, 89)),
            '10 pixel from right bottom should be black'
        );

        $this->assertEquals(
            127,
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 0, 0))["alpha"],
            'Left top pixel should be transparent'
        );

        $this->assertEquals(
            127,
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 99, 99))["alpha"],
            'Bottom right pixel should be transparent'
        );

        $this->assertEquals(
            127,
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 9, 9))["alpha"],
            '9 pixel from left top should be transparent'
        );

        $this->assertEquals(
            127,
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 90, 90))["alpha"],
            '9 pixel from bottom right should be transparent'
        );
    }

    public function testConvertToPaletteImage()
    {
        $image = imagecreatetruecolor(100, 100);

        // Whole image black
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));

        // Bottom right quater transparent
        imagealphablending($image, false);
        imagefilledrectangle($image, 50, 50, 100, 100, imagecolorallocatealpha($image, 0, 0, 0, 127));

        $image = new GdImage($image);
        $image->convertToPaletteImage();

        $this->assertInternalType('resource', $image->getResource());
        $this->assertFalse(imageistruecolor($image->getResource()));
        $this->assertEquals(
            ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0],
            imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 0, 0)),
            'Left top pixel should be black'
        );
        $this->assertEquals(
            127,
            imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 75, 75))["alpha"],
            'Bottom right quater should be transparent'
        );
    }

    public function testConvertToPaletteImageFromTrueColor()
    {
        $image = imagecreatetruecolor(100, 100);

        for ($x = 0; $x < 100; $x++) {
            for ($y = 0; $y < 100; $y++) {
                imagefilledrectangle($image, $x, $y, $x + 1, $y + 1, imagecolorallocatealpha($image, $x, $y, 0, 0));
            }
        }

        // Bottom right pixel transparent
        imagealphablending($image, false);
        imagefilledrectangle($image, 99, 99, 100, 100, imagecolorallocatealpha($image, 0, 0, 0, 127));

        $image = new GdImage($image);
        $image->convertToPaletteImage();

        $this->assertInternalType('resource', $image->getResource());
        $this->assertFalse(imageistruecolor($image->getResource()));
        $this->assertEquals(256, imagecolorstotal($image->getResource()));
        $this->assertEquals(
            127,
            imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 99, 99))["alpha"],
            'Bottom right pixel should be transparent'
        );
    }

    public function testCountColors()
    {
        $image = imagecreatetruecolor(100, 100);
        imagealphablending($image, false);

        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 0, 0, 0));
        imagefilledrectangle($image, 50, 0, 100, 50, imagecolorallocatealpha($image, 0, 255, 0, 0));
        imagefilledrectangle($image, 0, 50, 50, 100, imagecolorallocatealpha($image, 0, 0, 255, 0));
        imagefilledrectangle($image, 50, 50, 100, 100, imagecolorallocatealpha($image, 0, 0, 0, 127));

        $image = new GdImage($image);

        $this->assertEquals(4, $image->countColors());
        $this->assertEquals(4, $image->countColors(256));
        $this->assertEquals(2, $image->countColors(1));
    }

    public function testIsSemitransparent()
    {
        $image = imagecreatetruecolor(100, 100);
        imagealphablending($image, false);

        $image = new GdImage($image);

        imagefill($image->getResource(), 0, 0, imagecolorallocatealpha($image->getResource(), 0, 0, 0, 0));
        $this->assertFalse($image->isSemitransparent());

        imagefill($image->getResource(), 0, 0, imagecolorallocatealpha($image->getResource(), 0, 0, 0, 127));
        $this->assertFalse($image->isSemitransparent());

        imagefill($image->getResource(), 0, 0, imagecolorallocatealpha($image->getResource(), 0, 0, 0, 126));
        $this->assertTrue($image->isSemitransparent());

        imagefill($image->getResource(), 0, 0, imagecolorallocatealpha($image->getResource(), 0, 0, 0, 1));
        $this->assertTrue($image->isSemitransparent());

        imagefill($image->getResource(), 0, 0, imagecolorallocatealpha($image->getResource(), 0, 0, 0, 0));
        $this->assertFalse($image->isSemitransparent());
    }
}

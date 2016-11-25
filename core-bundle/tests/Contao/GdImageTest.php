<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Contao;

use Contao\CoreBundle\Test\TestCase;
use Contao\GdImage;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the GdImage class.
 *
 * @author Martin Auswöger <https://github.com/ausi>
 * @author Yanick Witschi <https://github.com/Toflar>
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @group legacy
 */
class GdImageTest extends TestCase
{
    /**
     * @var string
     */
    private static $rootDir;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        self::$rootDir = __DIR__.'/../../tmp';

        $fs = new Filesystem();
        $fs->mkdir(self::$rootDir);
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
        parent::setUp();

        define('TL_ROOT', self::$rootDir);
        System::setContainer($this->mockContainerWithContaoScopes());
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $resource = imagecreate(1, 1);
        $image = new GdImage($resource);

        $this->assertInstanceOf('Contao\GdImage', $image);
        $this->assertSame($resource, $image->getResource());
    }

    /**
     * Tests the fromDimensions() method.
     */
    public function testFromDimension()
    {
        $image = GdImage::fromDimensions(100, 100);

        $this->assertInternalType('resource', $image->getResource());
        $this->assertTrue(imageistruecolor($image->getResource()));
        $this->assertEquals(100, imagesx($image->getResource()));
        $this->assertEquals(100, imagesy($image->getResource()));

        $this->assertEquals(
            127,
            imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 0, 0))['alpha'],
            'Image should be transparent'
        );

        $this->assertEquals(
            127,
            imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 99, 99))['alpha'],
            'Image should be transparent'
        );
    }

    /**
     * Tests the fromFile() method.
     *
     * @param string $type
     *
     * @dataProvider getImageTypes
     */
    public function testFromFile($type)
    {
        $image = imagecreatetruecolor(100, 100);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));

        $method = 'image'.$type;
        $method($image, self::$rootDir.'/test.'.$type);
        imagedestroy($image);

        $image = GdImage::fromFile(new \File('test.'.$type));

        $this->assertInternalType('resource', $image->getResource());
        $this->assertEquals(100, imagesx($image->getResource()));
        $this->assertEquals(100, imagesy($image->getResource()));
    }

    /**
     * Tests the fromFile() method with an invalid type.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testFromFileInvalidType()
    {
        GdImage::fromFile(new \File('test.xyz'));
    }

    /**
     * Tests the saveToFile() method.
     *
     * @param string $type
     *
     * @dataProvider getImageTypes
     */
    public function testSaveToFile($type)
    {
        $file = self::$rootDir.'/test.'.$type;

        $image = GdImage::fromDimensions(100, 100);
        $image->saveToFile($file);

        $this->assertFileExists($file);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        $this->assertEquals('image/'.$type, $finfo->file($file));
    }

    /**
     * Tests the copyTo() method.
     */
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
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 0, 0))['alpha'],
            'Left top pixel should be transparent'
        );

        $this->assertEquals(
            127,
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 99, 99))['alpha'],
            'Bottom right pixel should be transparent'
        );

        $this->assertEquals(
            127,
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 9, 9))['alpha'],
            '9 pixel from left top should be transparent'
        );

        $this->assertEquals(
            127,
            imagecolorsforindex($target->getResource(), imagecolorat($target->getResource(), 90, 90))['alpha'],
            '9 pixel from bottom right should be transparent'
        );
    }

    /**
     * Tests the convertToPaletteImage() method.
     */
    public function testConvertToPaletteImage()
    {
        $image = imagecreatetruecolor(100, 100);

        // Whole image black
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));

        // Bottom right 25% transparent
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
            imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 75, 75))['alpha'],
            'Bottom right quater should be transparent'
        );
    }

    /**
     * Tests the convertToPaletteImage() method from a true color image.
     */
    public function testConvertToPaletteImageFromTrueColor()
    {
        $image = imagecreatetruecolor(100, 100);

        for ($x = 0; $x < 100; ++$x) {
            for ($y = 0; $y < 100; ++$y) {
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
            imagecolorsforindex($image->getResource(), imagecolorat($image->getResource(), 99, 99))['alpha'],
            'Bottom right pixel should be transparent'
        );
    }

    /**
     * Tests the countColors() method.
     */
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

    /**
     * Tests the isSemitransparent() method.
     */
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

    /**
     * Provides the image types for the tests.
     *
     * @return array
     */
    public function getImageTypes()
    {
        return [
            ['gif'],
            ['jpeg'],
            ['png'],
        ];
    }
}

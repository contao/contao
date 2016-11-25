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
use Contao\File;
use Contao\Picture;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests the Picture class.
 *
 * @author Martin Auswöger <https://github.com/ausi>
 * @author Yanick Witschi <https://github.com/Toflar>
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @group legacy
 */
class PictureTest extends TestCase
{
    private static $rootDir;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        self::$rootDir = __DIR__.'/../../tmp';

        $fs = new Filesystem();
        $fs->mkdir(self::$rootDir);
        $fs->mkdir(self::$rootDir.'/assets');
        $fs->mkdir(self::$rootDir.'/assets/images');

        foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f'] as $subdir) {
            $fs->mkdir(self::$rootDir.'/assets/images/'.$subdir);
        }

        $fs->mkdir(self::$rootDir.'/system');
        $fs->mkdir(self::$rootDir.'/system/tmp');
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

        copy(__DIR__.'/../Fixtures/images/dummy.jpg', self::$rootDir.'/dummy.jpg');

        $GLOBALS['TL_CONFIG']['debugMode'] = false;
        $GLOBALS['TL_CONFIG']['gdMaxImgWidth'] = 3000;
        $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] = 3000;
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpeg,jpg,svg,svgz';

        define('TL_ERROR', 'ERROR');
        define('TL_FILES_URL', '');
        define('TL_ROOT', self::$rootDir);

        $container = $this->mockContainerWithContaoScopes();
        $this->addImageServicesToContainer($container, self::$rootDir);

        System::setContainer($container);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileMock */
        $fileMock = $this
            ->getMockBuilder('Contao\File')
            ->setMethods(['__get', 'exists'])
            ->setConstructorArgs(['dummy.jpg'])
            ->getMock()
        ;

        $fileMock
            ->expects($this->any())
            ->method('exists')
            ->will($this->returnValue(true))
        ;

        $fileMock
            ->expects($this->any())
            ->method('__get')
            ->will($this->returnCallback(
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
            ))
        ;

        $this->assertInstanceOf('Contao\Picture', new Picture($fileMock));
    }

    /**
     * Tests the getTemplateData() method.
     */
    public function testGetTemplateData()
    {
        $picture = new Picture(new \File('dummy.jpg'));

        $picture->setImageSize((object) [
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

    /**
     * Tests the getTemplateData() method with an img tag only.
     */
    public function testGetTemplateDataImgOnly()
    {
        $picture = new Picture(new \File('dummy.jpg'));

        $picture->setImageSize((object) [
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertEquals(100, $pictureData['img']['width']);
        $this->assertEquals(100, $pictureData['img']['height']);

        $this->assertEquals(
            $pictureData['img']['src'],
            $pictureData['img']['srcset'],
            'Attributes src and srcset should be equal'
        );

        $this->assertEquals([], $pictureData['sources']);
    }

    /**
     * Tests the getTemplateData() method with sources.
     */
    public function testGetTemplateDataWithSources()
    {
        $picture = new Picture(new \File('dummy.jpg'));

        $picture->setImageSize((object) [
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
        ]);

        $picture->setImageSizeItems([
            (object) [
                'width' => 50,
                'height' => 50,
                'resizeMode' => 'crop',
                'zoom' => 0,
                'media' => '(max-width: 900px)',
            ],
            (object) [
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

        $this->assertEquals(
            $pictureData['img']['src'],
            $pictureData['img']['srcset'],
            'Attributes src and srcset should be equal'
        );

        $this->assertEquals(50, $pictureData['sources'][0]['width']);
        $this->assertEquals(50, $pictureData['sources'][0]['height']);
        $this->assertEquals('(max-width: 900px)', $pictureData['sources'][0]['media']);

        $this->assertEquals(
            $pictureData['sources'][0]['src'],
            $pictureData['sources'][0]['srcset'],
            'Attributes src and srcset should be equal'
        );

        $this->assertEquals(25, $pictureData['sources'][1]['width']);
        $this->assertEquals(25, $pictureData['sources'][1]['height']);
        $this->assertEquals('(max-width: 600px)', $pictureData['sources'][1]['media']);

        $this->assertEquals(
            $pictureData['sources'][1]['src'],
            $pictureData['sources'][1]['srcset'],
            'Attributes src and srcset should be equal'
        );
    }

    /**
     * Tests the getTemplateData() method with densities.
     */
    public function testGetTemplateDataWithDensities()
    {
        $picture = new Picture(new \File('dummy.jpg'));

        $picture->setImageSize((object) [
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
        $this->assertRegExp('(\.jpg\s+1x(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\.jpg\s+0\.5x(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\.jpg\s+2x(,|$))', $pictureData['img']['srcset']);
        $this->assertEquals([], $pictureData['sources']);
    }

    /**
     * Tests the getTemplateData() method with densities and sizes.
     */
    public function testGetTemplateDataWithDensitiesSizes()
    {
        $picture = new Picture(new \File('dummy.jpg'));

        $picture->setImageSize((object) [
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
        $this->assertRegExp('(\.jpg\s+100w(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\.jpg\s+50w(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\.jpg\s+200w(,|$))', $pictureData['img']['srcset']);
        $this->assertEquals([], $pictureData['sources']);
    }

    /**
     * Tests the getTemplateData() method with encoded file names.
     */
    public function testGetTemplateDataUrlEncoded()
    {
        copy(__DIR__.'/../Fixtures/images/dummy.jpg', self::$rootDir.'/dummy with spaces.jpg');

        $picture = new Picture(new \File('dummy with spaces.jpg'));

        $picture->setImageSize((object) [
            'width' => 0,
            'height' => 0,
            'resizeMode' => '',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertEquals(200, $pictureData['img']['width']);
        $this->assertEquals(200, $pictureData['img']['height']);
        $this->assertEquals('dummy%20with%20spaces.jpg', $pictureData['img']['src']);
        $this->assertEquals('dummy%20with%20spaces.jpg', $pictureData['img']['srcset']);
        $this->assertEquals([], $pictureData['sources']);
    }

    /**
     * Tests the getTemplateData() method with an old resize mode.
     */
    public function testGetTemplateDataOldResizeMode()
    {
        $picture = new Picture(new \File('dummy.jpg'));

        $picture->setImageSize((object) [
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'center_center',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertEquals(100, $pictureData['img']['width']);
        $this->assertEquals(100, $pictureData['img']['height']);

        $this->assertEquals(
            $pictureData['img']['src'],
            $pictureData['img']['srcset'],
            'Attributes src and srcset should be equal'
        );

        $this->assertEquals([], $pictureData['sources']);
    }
}

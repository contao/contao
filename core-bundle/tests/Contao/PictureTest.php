<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Contao\Picture;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group contao3
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PictureTest extends TestCase
{
    private static $rootDir;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

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
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $fs = new Filesystem();
        $fs->remove(self::$rootDir);
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        copy(__DIR__.'/../Fixtures/images/dummy.jpg', self::$rootDir.'/dummy.jpg');

        $GLOBALS['TL_CONFIG']['debugMode'] = false;
        $GLOBALS['TL_CONFIG']['gdMaxImgWidth'] = 3000;
        $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] = 3000;
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpeg,jpg,svg,svgz';

        define('TL_ERROR', 'ERROR');
        define('TL_FILES_URL', 'http://example.com/');
        define('TL_ROOT', self::$rootDir);

        $container = $this->mockContainerWithContaoScopes();
        $this->addImageServicesToContainer($container, self::$rootDir);

        System::setContainer($container);
    }

    public function testCanBeInstantiated(): void
    {
        $fileMock = $this->createMock(File::class);

        $fileMock
            ->method('exists')
            ->will($this->returnValue(true))
        ;

        $fileMock
            ->method('__get')
            ->will($this->returnCallback(
                function (string $key): ?string {
                    switch ($key) {
                        case 'extension':
                            return 'jpg';

                        case 'path':
                            return 'dummy.jpg';
                    }

                    return null;
                }
            ))
        ;

        $this->assertInstanceOf('Contao\Picture', new Picture($fileMock));
    }

    public function testReturnsTheTemplateData(): void
    {
        $picture = new Picture(new File('dummy.jpg'));

        $picture->setImageSize((object) [
            'width' => 0,
            'height' => 0,
            'resizeMode' => '',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertSame(200, $pictureData['img']['width']);
        $this->assertSame(200, $pictureData['img']['height']);
        $this->assertSame('http://example.com/dummy.jpg', $pictureData['img']['src']);
        $this->assertSame('http://example.com/dummy.jpg', $pictureData['img']['srcset']);
        $this->assertSame([], $pictureData['sources']);
    }

    public function testHandlesImages(): void
    {
        $picture = new Picture(new File('dummy.jpg'));

        $picture->setImageSize((object) [
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertSame(100, $pictureData['img']['width']);
        $this->assertSame(100, $pictureData['img']['height']);

        $this->assertSame(
            $pictureData['img']['src'],
            $pictureData['img']['srcset'],
            'Attributes src and srcset should be equal'
        );

        $this->assertSame([], $pictureData['sources']);
    }

    public function testHandlesImagesWithSources(): void
    {
        $picture = new Picture(new File('dummy.jpg'));

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

        $this->assertSame(100, $pictureData['img']['width']);
        $this->assertSame(100, $pictureData['img']['height']);

        $this->assertSame(
            $pictureData['img']['src'],
            $pictureData['img']['srcset'],
            'Attributes src and srcset should be equal'
        );

        $this->assertSame(50, $pictureData['sources'][0]['width']);
        $this->assertSame(50, $pictureData['sources'][0]['height']);
        $this->assertSame('(max-width: 900px)', $pictureData['sources'][0]['media']);

        $this->assertSame(
            $pictureData['sources'][0]['src'],
            $pictureData['sources'][0]['srcset'],
            'Attributes src and srcset should be equal'
        );

        $this->assertSame(25, $pictureData['sources'][1]['width']);
        $this->assertSame(25, $pictureData['sources'][1]['height']);
        $this->assertSame('(max-width: 600px)', $pictureData['sources'][1]['media']);

        $this->assertSame(
            $pictureData['sources'][1]['src'],
            $pictureData['sources'][1]['srcset'],
            'Attributes src and srcset should be equal'
        );
    }

    public function testHandlesImagesWithDensities(): void
    {
        $picture = new Picture(new File('dummy.jpg'));

        $picture->setImageSize((object) [
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
            'densities' => '0.5x, 2x',
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertSame(100, $pictureData['img']['width']);
        $this->assertSame(100, $pictureData['img']['height']);
        $this->assertCount(1, explode(',', $pictureData['img']['src']));
        $this->assertCount(3, explode(',', $pictureData['img']['srcset']));
        $this->assertRegExp('(\.jpg\s+1x(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\.jpg\s+0\.5x(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\.jpg\s+2x(,|$))', $pictureData['img']['srcset']);
        $this->assertSame([], $pictureData['sources']);
    }

    public function testHandlesImagesWithDensitiesAndSizes(): void
    {
        $picture = new Picture(new File('dummy.jpg'));

        $picture->setImageSize((object) [
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'crop',
            'zoom' => 0,
            'densities' => '0.5x, 2x',
            'sizes' => '100vw',
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertSame(100, $pictureData['img']['width']);
        $this->assertSame(100, $pictureData['img']['height']);
        $this->assertSame('100vw', $pictureData['img']['sizes']);
        $this->assertCount(1, explode(',', $pictureData['img']['src']));
        $this->assertCount(3, explode(',', $pictureData['img']['srcset']));
        $this->assertRegExp('(\.jpg\s+100w(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\.jpg\s+50w(,|$))', $pictureData['img']['srcset']);
        $this->assertRegExp('(\.jpg\s+200w(,|$))', $pictureData['img']['srcset']);
        $this->assertSame([], $pictureData['sources']);
    }

    public function testEncodesFileNames(): void
    {
        copy(__DIR__.'/../Fixtures/images/dummy.jpg', self::$rootDir.'/dummy with spaces.jpg');

        $picture = new Picture(new File('dummy with spaces.jpg'));

        $picture->setImageSize((object) [
            'width' => 0,
            'height' => 0,
            'resizeMode' => '',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertSame(200, $pictureData['img']['width']);
        $this->assertSame(200, $pictureData['img']['height']);
        $this->assertSame('http://example.com/dummy%20with%20spaces.jpg', $pictureData['img']['src']);
        $this->assertSame('http://example.com/dummy%20with%20spaces.jpg', $pictureData['img']['srcset']);
        $this->assertSame([], $pictureData['sources']);
    }

    public function testSupportsTheOldResizeMode(): void
    {
        $picture = new Picture(new File('dummy.jpg'));

        $picture->setImageSize((object) [
            'width' => 100,
            'height' => 100,
            'resizeMode' => 'center_center',
            'zoom' => 0,
        ]);

        $pictureData = $picture->getTemplateData();

        $this->assertSame(100, $pictureData['img']['width']);
        $this->assertSame(100, $pictureData['img']['height']);

        $this->assertSame(
            $pictureData['img']['src'],
            $pictureData['img']['srcset'],
            'Attributes src and srcset should be equal'
        );

        $this->assertSame([], $pictureData['sources']);
    }
}

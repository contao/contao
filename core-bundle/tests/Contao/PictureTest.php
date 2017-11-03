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

use Contao\Config;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Contao\FilesModel;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Contao\Picture;
use Contao\System;
use Imagine\Gd\Imagine as ImagineGd;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group contao3
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PictureTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fs = new Filesystem();
        $fs->mkdir(static::getTempDir().'/assets');
        $fs->mkdir(static::getTempDir().'/assets/images');

        foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f'] as $subdir) {
            $fs->mkdir(static::getTempDir().'/assets/images/'.$subdir);
        }

        $fs->mkdir(static::getTempDir().'/system');
        $fs->mkdir(static::getTempDir().'/system/tmp');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        copy(__DIR__.'/../Fixtures/images/dummy.jpg', $this->getTempDir().'/dummy.jpg');

        $GLOBALS['TL_CONFIG']['debugMode'] = false;
        $GLOBALS['TL_CONFIG']['gdMaxImgWidth'] = 3000;
        $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] = 3000;
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpeg,jpg,svg,svgz';

        \define('TL_ERROR', 'ERROR');
        \define('TL_FILES_URL', 'http://example.com/');
        \define('TL_ROOT', $this->getTempDir());

        System::setContainer($this->mockContainerWithImageServices());
    }

    public function testCanBeInstantiated(): void
    {
        $properties = [
            'extension' => 'jpg',
            'path' => 'dummy.jpg',
        ];

        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileMock */
        $fileMock = $this->mockClassWithProperties(File::class, $properties);

        $fileMock
            ->method('exists')
            ->willReturn(true)
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
        copy(__DIR__.'/../Fixtures/images/dummy.jpg', $this->getTempDir().'/dummy with spaces.jpg');

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

    /**
     * Mocks a container with image services.
     *
     * @return ContainerBuilder
     */
    private function mockContainerWithImageServices(): ContainerBuilder
    {
        $filesystem = new Filesystem();

        $adapters = [
            Config::class => $this->mockConfiguredAdapter(['get' => 3000]),
            FilesModel::class => $this->mockConfiguredAdapter(['findByPath' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $container = $this->mockContainer($this->getTempDir());
        $container->setParameter('contao.web_dir', $this->getTempDir().'/web');
        $container->setParameter('contao.image.target_dir', $this->getTempDir().'/assets/images');

        $resizer = new LegacyResizer($container->getParameter('contao.image.target_dir'), new ResizeCalculator());
        $resizer->setFramework($framework);

        $imageFactory = new ImageFactory(
            $resizer,
            new ImagineGd(),
            new ImagineSvg(),
            $filesystem,
            $framework,
            $container->getParameter('contao.image.bypass_cache'),
            $container->getParameter('contao.image.imagine_options'),
            $container->getParameter('contao.image.valid_extensions')
        );

        $pictureGenerator = new PictureGenerator($resizer);

        $container->set('contao.image.image_factory', $imageFactory);
        $container->set('contao.image.picture_generator', $pictureGenerator);

        return $container;
    }
}

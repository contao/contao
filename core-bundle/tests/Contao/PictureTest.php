<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Config;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Contao\Files;
use Contao\FilesModel;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Contao\Picture;
use Contao\System;
use Imagine\Gd\Imagine as ImagineGd;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class PictureTest extends TestCase
{
    use ExpectDeprecationTrait;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();

        $this->filesystem->mkdir(static::getTempDir().'/assets');
        $this->filesystem->mkdir(static::getTempDir().'/assets/images');

        foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f'] as $subdir) {
            $this->filesystem->mkdir(static::getTempDir().'/assets/images/'.$subdir);
        }

        $this->filesystem->mkdir(static::getTempDir().'/system');
        $this->filesystem->mkdir(static::getTempDir().'/system/tmp');

        $this->filesystem->copy(__DIR__.'/../Fixtures/images/dummy.jpg', $this->getTempDir().'/dummy.jpg');

        $GLOBALS['TL_CONFIG']['debugMode'] = false;
        $GLOBALS['TL_CONFIG']['gdMaxImgWidth'] = 3000;
        $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] = 3000;
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpeg,jpg,svg,svgz';

        System::setContainer($this->getContainerWithImageServices());
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class, File::class, Files::class]);

        parent::tearDown();
    }

    /**
     * @group legacy
     */
    public function testReturnsTheTemplateData(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

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

    /**
     * @group legacy
     */
    public function testHandlesImages(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

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

    /**
     * @group legacy
     */
    public function testHandlesImagesWithSources(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

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

    /**
     * @group legacy
     */
    public function testHandlesImagesWithDensities(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

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
        $this->assertMatchesRegularExpression('(\.jpg\s+1x(,|$))', $pictureData['img']['srcset']);
        $this->assertMatchesRegularExpression('(\.jpg\s+0\.5x(,|$))', $pictureData['img']['srcset']);
        $this->assertMatchesRegularExpression('(\.jpg\s+2x(,|$))', $pictureData['img']['srcset']);
        $this->assertSame([], $pictureData['sources']);
    }

    /**
     * @group legacy
     */
    public function testHandlesImagesWithDensitiesAndSizes(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

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
        $this->assertMatchesRegularExpression('(\.jpg\s+100w(,|$))', $pictureData['img']['srcset']);
        $this->assertMatchesRegularExpression('(\.jpg\s+50w(,|$))', $pictureData['img']['srcset']);
        $this->assertMatchesRegularExpression('(\.jpg\s+200w(,|$))', $pictureData['img']['srcset']);
        $this->assertSame([], $pictureData['sources']);
    }

    /**
     * @group legacy
     */
    public function testEncodesFileNames(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

        $this->filesystem->copy(__DIR__.'/../Fixtures/images/dummy.jpg', $this->getTempDir().'/dummy with spaces.jpg');

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

    /**
     * @group legacy
     */
    public function testSupportsTheOldResizeMode(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using the "Contao\Image" class has been deprecated %s.');

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

    private function getContainerWithImageServices(): ContainerBuilder
    {
        $filesystem = new Filesystem();

        $adapters = [
            Config::class => $this->mockConfiguredAdapter(['get' => 3000]),
            FilesModel::class => $this->mockConfiguredAdapter(['findByPath' => null]),
        ];

        $context = $this->createMock(ContaoContext::class);
        $context
            ->method('getStaticUrl')
            ->willReturn('http://example.com/')
        ;

        $framework = $this->mockContaoFramework($adapters);

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->setParameter('contao.web_dir', $this->getTempDir().'/public');
        $container->setParameter('contao.image.target_dir', $this->getTempDir().'/assets/images');
        $container->set('contao.assets.files_context', $context);

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
            $container->getParameter('contao.image.valid_extensions'),
            $container->getParameter('kernel.project_dir').'/'.$container->getParameter('contao.upload_path')
        );

        $pictureGenerator = new PictureGenerator($resizer);

        $pictureFactory = new PictureFactory(
            $pictureGenerator,
            $imageFactory,
            $framework,
            $container->getParameter('contao.image.bypass_cache'),
            $container->getParameter('contao.image.imagine_options')
        );

        $container->set('contao.image.factory', $imageFactory);
        $container->set('contao.image.picture_generator', $pictureGenerator);
        $container->set('contao.image.picture_factory', $pictureFactory);

        return $container;
    }
}

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

use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Contao\Image;
use Contao\Image\ResizeCalculator;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class ImageTest extends TestCase
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

        System::setContainer($this->mockContainerWithImageServices());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testFailsIfTheFileDoesNotExist(): void
    {
        $fileMock = $this->createMock(File::class);
        $fileMock
            ->method('exists')
            ->willReturn(false)
        ;

        $this->expectException('InvalidArgumentException');

        new Image($fileMock);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testFailsIfTheFileExtensionIsInvalid(): void
    {
        /** @var File|MockObject $fileMock */
        $fileMock = $this->mockClassWithProperties(File::class, ['extension' => 'foobar']);
        $fileMock
            ->method('exists')
            ->willReturn(true)
        ;

        $this->expectException('InvalidArgumentException');

        new Image($fileMock);
    }

    /**
     * @group legacy
     * @dataProvider getComputeResizeDataWithoutImportantPart
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testResizesImagesWithoutImportantPart(array $arguments, array $expectedResult): void
    {
        $properties = [
            'extension' => 'jpg',
            'path' => 'dummy.jpg',
            'viewWidth' => $arguments[2],
            'viewHeight' => $arguments[3],
        ];

        /** @var File|MockObject $fileMock */
        $fileMock = $this->mockClassWithProperties(File::class, $properties);
        $fileMock
            ->method('exists')
            ->willReturn(true)
        ;

        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[4]);

        $this->assertSame(
            $expectedResult,
            $imageObj->computeResize()
        );

        $imageObj->setZoomLevel(50);

        $this->assertSame(
            $expectedResult,
            $imageObj->computeResize(),
            'Zoom 50 should return the same results if no important part is specified'
        );

        $imageObj->setZoomLevel(100);

        $this->assertSame(
            $expectedResult,
            $imageObj->computeResize(),
            'Zoom 100 should return the same results if no important part is specified'
        );
    }

    public function getComputeResizeDataWithoutImportantPart(): \Generator
    {
        yield 'No dimensions' => [
            [null, null, 100, 100, null],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Same dimensions' => [
            [100, 100, 100, 100, null],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Scale down' => [
            [50, 50, 100, 100, null],
            [
                'width' => 50,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 50,
            ],
        ];

        yield 'Scale up' => [
            [100, 100, 50, 50, null],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Width only' => [
            [100, null, 50, 50, null],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Height only' => [
            [null, 100, 50, 50, null],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Crop landscape' => [
            [100, 50, 100, 100, null],
            [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => -25,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Crop portrait' => [
            [50, 100, 100, 100, null],
            [
                'width' => 50,
                'height' => 100,
                'target_x' => -25,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Mode proportional landscape' => [
            [100, 10, 100, 50, 'proportional'],
            [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 50,
            ],
        ];

        yield 'Mode proportional portrait' => [
            [10, 100, 50, 100, 'proportional'],
            [
                'width' => 50,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 100,
            ],
        ];

        yield 'Mode proportional square' => [
            [100, 50, 100, 100, 'proportional'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Mode box landscape 1' => [
            [100, 100, 100, 50, 'box'],
            [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 50,
            ],
        ];

        yield 'Mode box landscape 2' => [
            [100, 10, 100, 50, 'box'],
            [
                'width' => 20,
                'height' => 10,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 20,
                'target_height' => 10,
            ],
        ];

        yield 'Mode box portrait 1' => [
            [100, 100, 50, 100, 'box'],
            [
                'width' => 50,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 100,
            ],
        ];

        yield 'Mode box portrait 2' => [
            [10, 100, 50, 100, 'box'],
            [
                'width' => 10,
                'height' => 20,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 10,
                'target_height' => 20,
            ],
        ];

        yield 'Mode left_top landscape' => [
            [100, 100, 100, 50, 'left_top'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode left_top portrait' => [
            [100, 100, 50, 100, 'left_top'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode center_top landscape' => [
            [100, 100, 100, 50, 'center_top'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode center_top portrait' => [
            [100, 100, 50, 100, 'center_top'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode right_top landscape' => [
            [100, 100, 100, 50, 'right_top'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode right_top portrait' => [
            [100, 100, 50, 100, 'right_top'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode left_center landscape' => [
            [100, 100, 100, 50, 'left_center'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode left_center portrait' => [
            [100, 100, 50, 100, 'left_center'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode center_center landscape' => [
            [100, 100, 100, 50, 'center_center'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode center_center portrait' => [
            [100, 100, 50, 100, 'center_center'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode right_center landscape' => [
            [100, 100, 100, 50, 'right_center'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode right_center portrait' => [
            [100, 100, 50, 100, 'right_center'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode left_bottom landscape' => [
            [100, 100, 100, 50, 'left_bottom'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode left_bottom portrait' => [
            [100, 100, 50, 100, 'left_bottom'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode center_bottom landscape' => [
            [100, 100, 100, 50, 'center_bottom'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode center_bottom portrait' => [
            [100, 100, 50, 100, 'center_bottom'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Mode right_bottom landscape' => [
            [100, 100, 100, 50, 'right_bottom'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ],
        ];

        yield 'Mode right_bottom portrait' => [
            [100, 100, 50, 100, 'right_bottom'],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ],
        ];

        yield 'Float values' => [
            [100.4, 100.4, 50, 50, null],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];
    }

    /**
     * @group legacy
     * @dataProvider getComputeResizeDataWithImportantPart
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testResizesImagesWithImportantPart(array $arguments, array $expectedResult): void
    {
        $properties = [
            'extension' => 'jpg',
            'path' => 'dummy.jpg',
            'viewWidth' => $arguments[2],
            'viewHeight' => $arguments[3],
        ];

        /** @var File|MockObject $fileMock */
        $fileMock = $this->mockClassWithProperties(File::class, $properties);
        $fileMock
            ->method('exists')
            ->willReturn(true)
        ;

        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[4]);
        $imageObj->setZoomLevel($arguments[5]);
        $imageObj->setImportantPart($arguments[6]);

        $this->assertSame($expectedResult, $imageObj->computeResize());
    }

    public function getComputeResizeDataWithImportantPart(): \Generator
    {
        yield 'No dimensions zoom 0' => [
            [null, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'No dimensions zoom 50' => [
            [null, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 80,
                'height' => 80,
                'target_x' => -10,
                'target_y' => -10,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'No dimensions zoom 100' => [
            [null, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 60,
                'height' => 60,
                'target_x' => -20,
                'target_y' => -20,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Width only zoom 0' => [
            [100, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Width only zoom 50' => [
            [100, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -13,
                'target_y' => -13,
                'target_width' => 125,
                'target_height' => 125,
            ],
        ];

        yield 'Width only zoom 100' => [
            [100, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -33,
                'target_y' => -33,
                'target_width' => 167,
                'target_height' => 167,
            ],
        ];

        yield 'Same dimensions zoom 0' => [
            [100, 100, 100, 100, null, 0, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ],
        ];

        yield 'Same dimensions zoom 50' => [
            [100, 100, 100, 100, null, 50, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -17,
                'target_y' => -17,
                'target_width' => 133,
                'target_height' => 133,
            ],
        ];

        yield 'Same dimensions zoom 100' => [
            [100, 100, 100, 100, null, 100, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]],
            [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => -50,
                'target_width' => 200,
                'target_height' => 200,
            ],
        ];

        yield 'Landscape to portrait zoom 0' => [
            [100, 200, 200, 100, null, 0, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]],
            [
                'width' => 100,
                'height' => 200,
                'target_x' => -233,
                'target_y' => 0,
                'target_width' => 400,
                'target_height' => 200,
            ],
        ];

        yield 'Landscape to portrait zoom 50' => [
            [100, 200, 200, 100, null, 50, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]],
            [
                'width' => 100,
                'height' => 200,
                'target_x' => -367,
                'target_y' => -43,
                'target_width' => 571,
                'target_height' => 286,
            ],
        ];

        yield 'Landscape to portrait zoom 100' => [
            [100, 200, 200, 100, null, 100, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]],
            [
                'width' => 100,
                'height' => 200,
                'target_x' => -700,
                'target_y' => -150,
                'target_width' => 1000,
                'target_height' => 500,
            ],
        ];
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testSupportsReadingAndWritingValues(): void
    {
        $properties = [
            'extension' => 'jpg',
            'path' => 'dummy.jpg',
            'width' => 100,
            'viewWidth' => 100,
            'height' => 100,
            'viewHeight' => 100,
        ];

        /** @var File|MockObject $fileMock */
        $fileMock = $this->mockClassWithProperties(File::class, $properties);
        $fileMock
            ->method('exists')
            ->willReturn(true)
        ;

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
            'height' => 60,
        ]);

        $this->assertSame($imageObj->getImportantPart(), [
            'x' => 20,
            'y' => 40,
            'width' => 80,
            'height' => 60,
        ]);

        $imageObj->setImportantPart([
            'x' => -20,
            'y' => 40.1,
            'width' => '80',
            'height' => 120,
        ]);

        $this->assertSame($imageObj->getImportantPart(), [
            'x' => 0,
            'y' => 40,
            'width' => 80,
            'height' => 60,
        ]);

        $imageObj->setImportantPart([
            'x' => 200,
            'y' => 200,
            'width' => 200,
            'height' => 200,
        ]);

        $this->assertSame($imageObj->getImportantPart(), [
            'x' => 99,
            'y' => 99,
            'width' => 1,
            'height' => 1,
        ]);

        $imageObj->setImportantPart();

        $this->assertSame($imageObj->getImportantPart(), [
            'x' => 0,
            'y' => 0,
            'width' => 100,
            'height' => 100,
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
     * @group legacy
     * @dataProvider getCacheName
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testReturnsTheCacheName(array $arguments, string $expectedCacheName): void
    {
        $properties = [
            'extension' => 'jpg',
            'path' => $arguments[2],
            'filename' => $arguments[2],
            'mtime' => $arguments[5],
            'width' => 200,
            'viewWidth' => 200,
            'height' => 200,
            'viewHeight' => 200,
        ];

        /** @var File|MockObject $fileMock */
        $fileMock = $this->mockClassWithProperties(File::class, $properties);
        $fileMock
            ->method('exists')
            ->willReturn(true)
        ;

        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[3]);
        $imageObj->setZoomLevel($arguments[4]);
        $imageObj->setImportantPart($arguments[6]);

        $this->assertSame($imageObj->getCacheName(), $expectedCacheName);
    }

    public function getCacheName(): \Generator
    {
        // target width, target height, file name (path), resize mode, zoom level, mtime, important part
        // expected cache name
        yield [
            [
                100,
                100,
                'dummy.jpg',
                'crop',
                0,
                12345678,
                ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60],
            ],
            'assets/images/c/dummy.jpg-fc94db8c.jpg',
        ];

        yield [
            [
                200,
                100,
                'test.jpg',
                'proportional',
                50,
                87654321,
                ['x' => 30, 'y' => 20, 'width' => 60, 'height' => 90],
            ],
            'assets/images/3/test.jpg-4e7b07e3.jpg',
        ];

        yield [
            [
                100,
                200,
                'other.jpg',
                'center_center',
                100,
                6666666,
                ['x' => 10, 'y' => 20, 'width' => 70, 'height' => 20],
            ],
            'assets/images/f/other.jpg-1fe4f44f.jpg',
        ];
    }

    /**
     * @group legacy
     * @dataProvider getZoomLevel
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testFailsIfTheZoomValueIsOutOfBounds(int $value): void
    {
        /** @var File|MockObject $fileMock */
        $fileMock = $this->mockClassWithProperties(File::class, ['extension' => 'jpg']);
        $fileMock
            ->method('exists')
            ->willReturn(true)
        ;

        $imageObj = new Image($fileMock);

        $this->expectException('InvalidArgumentException');

        $imageObj->setZoomLevel($value);
    }

    public function getZoomLevel(): \Generator
    {
        yield 'Underflow' => [-1];
        yield 'Overflow' => [101];
    }

    /**
     * @group legacy
     * @dataProvider getGetLegacy
     *
     * @expectedDeprecation Using Image::get() has been deprecated %s.
     */
    public function testFactorsImagesInTheLegacyMethod(array $arguments): void
    {
        $result = Image::get($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);

        $this->assertNull($result);
    }

    public function getGetLegacy(): \Generator
    {
        // original image, target width, target height, resize mode, target, force override
        yield 'No empty image path returns null' => [
            ['', 100, 100, 'crop', null, false],
        ];

        yield 'Inexistent file returns null' => [
            ['foobar.jpg', 100, 100, 'crop', null, false],
        ];
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using Image::get() has been deprecated %s.
     */
    public function testDoesNotFactorImagesInTheLegacyMethodIfTheArgumentIsInvalid(): void
    {
        $this->assertNull(Image::get('', 100, 100));
        $this->assertNull(Image::get(0, 100, 100));
        $this->assertNull(Image::get(null, 100, 100));
    }

    /**
     * @group legacy
     * @dataProvider getResizeLegacy
     *
     * @expectedDeprecation Using Image::resize() has been deprecated %s.
     */
    public function testResizesImagesInTheLegacyMethod(array $arguments): void
    {
        $result = Image::resize($arguments[0], $arguments[1], $arguments[2], $arguments[3]);

        $this->assertFalse($result);
    }

    public function getResizeLegacy(): \Generator
    {
        // original image, target width, target height, resize mode
        yield 'No empty image path returns false' => [
            ['', 100, 100, 'crop'],
        ];

        yield 'Inexistent file returns false' => [
            ['foobar.jpg', 100, 100, 'crop'],
        ];
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testDoesNotResizeMatchingImages(): void
    {
        $file = new File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(200)->setTargetHeight(200);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame($resultFile->width, 200);
        $this->assertSame($resultFile->height, 200);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testCropsImages(): void
    {
        $file = new File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame($resultFile->width, 100);
        $this->assertSame($resultFile->height, 100);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testCropsImagesWithTargetPath(): void
    {
        $file = new File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100)->setTargetPath('dummy_foobar.jpg');
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame($resultFile->width, 100);
        $this->assertSame($resultFile->height, 100);
        $this->assertSame($resultFile->path, 'dummy_foobar.jpg');
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testCropsImagesWithExistingTargetPath(): void
    {
        $file = new File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100)->setTargetPath('dummy_foobar.jpg');
        $imageObj->executeResize();

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame($resultFile->width, 100);
        $this->assertSame($resultFile->height, 100);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testResizesSvgImages(): void
    {
        file_put_contents(
            $this->getTempDir().'/dummy1.svg',
            '<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
            <svg
                version="1.1"
                xmlns="http://www.w3.org/2000/svg"
                width="400px"
                height="200px"
                viewBox="100 100 400 200"
            ></svg>'
        );

        $file = new File('dummy1.svg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame(100, $resultFile->width);
        $this->assertSame(100, $resultFile->height);

        $doc = new \DOMDocument();
        $doc->loadXML($resultFile->getContent());

        /** @var \DOMElement $firstChild */
        $firstChild = $doc->documentElement->firstChild;

        $this->assertSame('100 100 400 200', $firstChild->getAttribute('viewBox'));
        $this->assertSame('-50', $firstChild->getAttribute('x'));
        $this->assertSame('0', $firstChild->getAttribute('y'));
        $this->assertSame('200', $firstChild->getAttribute('width'));
        $this->assertSame('100', $firstChild->getAttribute('height'));
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testResizesSvgImagesWithPercentageDimensions(): void
    {
        file_put_contents(
            $this->getTempDir().'/dummy2.svg',
            '<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
            <svg
                version="1.1"
                xmlns="http://www.w3.org/2000/svg"
                width="100%"
                height="100%"
                viewBox="100 100 400 200"
            ></svg>'
        );

        $file = new File('dummy2.svg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame(100, $resultFile->width);
        $this->assertSame(100, $resultFile->height);

        $doc = new \DOMDocument();
        $doc->loadXML($resultFile->getContent());

        /** @var \DOMElement $firstChild */
        $firstChild = $doc->documentElement->firstChild;

        $this->assertSame('100 100 400 200', $firstChild->getAttribute('viewBox'));
        $this->assertSame('-50', $firstChild->getAttribute('x'));
        $this->assertSame('0', $firstChild->getAttribute('y'));
        $this->assertSame('200', $firstChild->getAttribute('width'));
        $this->assertSame('100', $firstChild->getAttribute('height'));
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testResizesSvgImagesWithoutDimensions(): void
    {
        file_put_contents(
            $this->getTempDir().'/dummy3.svg',
            '<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
            <svg
                version="1.1"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="100 100 400 200"
            ></svg>'
        );

        $file = new File('dummy3.svg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame(100, $resultFile->width);
        $this->assertSame(100, $resultFile->height);

        $doc = new \DOMDocument();
        $doc->loadXML($resultFile->getContent());

        /** @var \DOMElement $firstChild */
        $firstChild = $doc->documentElement->firstChild;

        $this->assertSame('100 100 400 200', $firstChild->getAttribute('viewBox'));
        $this->assertSame('-50', $firstChild->getAttribute('x'));
        $this->assertSame('0', $firstChild->getAttribute('y'));
        $this->assertSame('200', $firstChild->getAttribute('width'));
        $this->assertSame('100', $firstChild->getAttribute('height'));
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testResizesSvgImagesWithoutViewBox(): void
    {
        file_put_contents(
            $this->getTempDir().'/dummy4.svg',
            '<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
            <svg
                version="1.1"
                xmlns="http://www.w3.org/2000/svg"
                width="200.1em"
                height="100.1em"
            ></svg>'
        );

        $file = new File('dummy4.svg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame(100, $resultFile->width);
        $this->assertSame(100, $resultFile->height);

        $doc = new \DOMDocument();
        $doc->loadXML($resultFile->getContent());

        /** @var \DOMElement $firstChild */
        $firstChild = $doc->documentElement->firstChild;

        $this->assertSame('0 0 200.1 100.1', $firstChild->getAttribute('viewBox'));
        $this->assertSame('-50', $firstChild->getAttribute('x'));
        $this->assertSame('0', $firstChild->getAttribute('y'));
        $this->assertSame('200', $firstChild->getAttribute('width'));
        $this->assertSame('100', $firstChild->getAttribute('height'));
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testResizesSvgImagesWithoutViewBoxAndDimensions(): void
    {
        file_put_contents(
            $this->getTempDir().'/dummy5.svg',
            '<?xml version="1.0" encoding="utf-8"?>
            <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
            <svg
                version="1.1"
                xmlns="http://www.w3.org/2000/svg"
            ></svg>'
        );

        $file = new File('dummy5.svg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());

        $this->assertSame($file->path, $resultFile->path);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testResizesSvgzImages(): void
    {
        file_put_contents(
            $this->getTempDir().'/dummy.svgz',
            gzencode(
                '<?xml version="1.0" encoding="utf-8"?>
                <!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
                <svg
                    version="1.1"
                    xmlns="http://www.w3.org/2000/svg"
                    width="400px"
                    height="200px"
                    viewBox="100 100 400 200"
                ></svg>'
            )
        );

        $file = new File('dummy.svgz');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->executeResize();

        $resultFile = new File($imageObj->getResizedPath());
        $this->assertSame(100, $resultFile->width);
        $this->assertSame(100, $resultFile->height);

        $doc = new \DOMDocument();
        $doc->loadXML(gzdecode($resultFile->getContent()));

        /** @var \DOMElement $firstChild */
        $firstChild = $doc->documentElement->firstChild;

        $this->assertSame('100 100 400 200', $firstChild->getAttribute('viewBox'));
        $this->assertSame('-50', $firstChild->getAttribute('x'));
        $this->assertSame('0', $firstChild->getAttribute('y'));
        $this->assertSame('200', $firstChild->getAttribute('width'));
        $this->assertSame('100', $firstChild->getAttribute('height'));
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testExecutesTheResizeHook(): void
    {
        $GLOBALS['TL_HOOKS'] = [
            'executeResize' => [[\get_class($this), 'executeResizeHookCallback']],
        ];

        $file = new File('dummy.jpg');

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(100)->setTargetHeight(100);
        $imageObj->setTargetPath('target.jpg');
        $imageObj->executeResize();

        $this->assertSame(
            'assets/dummy.jpg%26executeResize_100_100_crop_target.jpg_Contao-Image.jpg',
            $imageObj->getResizedPath()
        );

        $imageObj = new Image($file);
        $imageObj->setTargetWidth($file->width)->setTargetHeight($file->height);
        $imageObj->executeResize();

        $this->assertSame(
            'assets/dummy.jpg%26executeResize_200_200_crop__Contao-Image.jpg',
            $imageObj->getResizedPath()
        );

        $imageObj = new Image($file);
        $imageObj->setTargetWidth($file->width)->setTargetHeight($file->height);

        file_put_contents($this->getTempDir().'/target.jpg', '');

        $imageObj->setTargetPath('target.jpg');
        $imageObj->executeResize();

        $this->assertSame(
            'assets/dummy.jpg%26executeResize_200_200_crop_target.jpg_Contao-Image.jpg',
            $imageObj->getResizedPath()
        );

        unset($GLOBALS['TL_HOOKS']);
    }

    public static function executeResizeHookCallback(Image $imageObj): string
    {
        // Do not include $cacheName as it is dynamic (mtime)
        $path = 'assets/'
            .$imageObj->getOriginalPath()
            .'&executeResize'
            .'_'.$imageObj->getTargetWidth()
            .'_'.$imageObj->getTargetHeight()
            .'_'.$imageObj->getResizeMode()
            .'_'.$imageObj->getTargetPath()
            .'_'.str_replace('\\', '-', \get_class($imageObj))
            .'.jpg'
        ;

        file_put_contents(System::getContainer()->getParameter('kernel.project_dir').'/'.$path, '');

        return $path;
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using new Contao\Image() has been deprecated %s.
     */
    public function testExecutesTheGetImageHook(): void
    {
        $file = new File('dummy.jpg');

        // Build cache before adding the hook
        $imageObj = new Image($file);
        $imageObj->setTargetWidth(50)->setTargetHeight(50);
        $imageObj->executeResize();

        $GLOBALS['TL_HOOKS'] = [
            'getImage' => [[\get_class($this), 'getImageHookCallback']],
        ];

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(120)->setTargetHeight(120);
        $imageObj->executeResize();

        $this->assertSame(
            'assets/dummy.jpg%26getImage_120_120_crop_Contao-File__Contao-Image.jpg',
            $imageObj->getResizedPath()
        );

        $imageObj = new Image($file);
        $imageObj->setTargetWidth(50)->setTargetHeight(50);
        $imageObj->executeResize();

        $this->assertRegExp(
            '(^assets/images/.*dummy.*.jpg$)',
            $imageObj->getResizedPath(),
            'Hook should not get called for cached images'
        );

        $imageObj = new Image($file);
        $imageObj->setTargetWidth($file->width)->setTargetHeight($file->height);
        $imageObj->executeResize();

        $this->assertSame(
            'dummy.jpg',
            $imageObj->getResizedPath(),
            'Hook should not get called if no resize is necessary'
        );

        unset($GLOBALS['TL_HOOKS']);
    }

    /**
     * @param object $fileObj
     * @param object $imageObj
     */
    public static function getImageHookCallback(string $originalPath, int $targetWidth, int $targetHeight, string $resizeMode, string $cacheName, $fileObj, string $targetPath, $imageObj): string
    {
        // Do not include $cacheName as it is dynamic (mtime)
        $path = 'assets/'
            .$originalPath
            .'&getImage'
            .'_'.$targetWidth
            .'_'.$targetHeight
            .'_'.$resizeMode
            .'_'.str_replace('\\', '-', \get_class($fileObj))
            .'_'.$targetPath
            .'_'.str_replace('\\', '-', \get_class($imageObj))
            .'.jpg'
        ;

        file_put_contents(System::getContainer()->getParameter('kernel.project_dir').'/'.$path, '');

        return $path;
    }

    /**
     * @group legacy
     * @dataProvider getGetPixelValueData
     *
     * @expectedDeprecation Using Image::getPixelValue() has been deprecated %s.
     */
    public function testReadsThePixelValue(string $value, int $expected): void
    {
        $this->assertSame($expected, Image::getPixelValue($value));
    }

    public function getGetPixelValueData(): \Generator
    {
        yield 'No unit' => ['1234.5', 1235];
        yield 'px' => ['1234.5px', 1235];
        yield 'em' => ['1em', 16];
        yield 'ex' => ['2ex', 16];
        yield 'pt' => ['12pt', 16];
        yield 'pc' => ['1pc', 16];
        yield 'in' => [(1 / 6).'in', 16];
        yield 'cm' => [(2.54 / 6).'cm', 16];
        yield 'mm' => [(25.4 / 6).'mm', 16];
        yield 'invalid' => ['abc', 0];
    }

    private function mockContainerWithImageServices(): ContainerBuilder
    {
        $container = $this->mockContainer($this->getTempDir());
        $container->setParameter('contao.image.target_dir', $this->getTempDir().'/assets/images');
        $container->setParameter('contao.web_dir', $this->getTempDir().'/web');

        $resizer = new LegacyResizer($container->getParameter('contao.image.target_dir'), new ResizeCalculator());
        $resizer->setFramework($this->mockContaoFramework());

        $container->set('contao.image.resizer', $resizer);
        $container->set('filesystem', new Filesystem());
        $container->set('monolog.logger.contao', new NullLogger());

        return $container;
    }
}

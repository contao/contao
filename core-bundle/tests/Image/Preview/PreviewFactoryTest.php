<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Preview;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Preview\MissingPreviewProviderException;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Image\Preview\PreviewProviderInterface;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\Image;
use Contao\Image\ImageInterface;
use Contao\Image\Picture;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class PreviewFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        (new Filesystem())->remove(Path::join($this->getTempDir(), 'assets/previews'));
        (new Filesystem())->remove(Path::join($this->getTempDir(), 'sources'));
    }

    public function testMissingPreviewProvider(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');

        $factory = new PreviewFactory(
            [],
            $this->createMock(ImageFactoryInterface::class),
            $this->createMock(PictureFactoryInterface::class),
            $this->createMock(Studio::class),
            $this->createMock(ContaoFramework::class),
            'not so secret ;)',
            Path::join($this->getTempDir(), 'assets/previews'),
            ['png'],
            128,
            499,
        );

        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreview($sourcePath);
    }

    public function testCreatePreview(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');

        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $factory = $this->createFactoryWithExampleProvider();
        $preview = $factory->createPreview($sourcePath);

        $this->assertFileExists($preview->getPath());
        $this->assertSame(128, $preview->getDimensions()->getSize()->getWidth());
        $this->assertSame(256, $preview->getDimensions()->getSize()->getHeight());
        $this->assertMatchesRegularExpression('(/[0-9a-z]/foo-[0-9a-z]{15}\.png$)', $preview->getPath());

        (new Filesystem())->dumpFile($sourcePath, 'not a PDF');

        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreview($sourcePath, 256);
    }

    public function testCreatePreviews(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');

        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $factory = $this->createFactoryWithExampleProvider();
        $previews = $factory->createPreviews($sourcePath, 200, 3);

        $this->assertCount(3, $previews);

        foreach ($previews as $preview) {
            $this->assertFileExists($preview->getPath());
            $this->assertSame(256, $preview->getDimensions()->getSize()->getWidth());
            $this->assertSame(512, $preview->getDimensions()->getSize()->getHeight());
            $this->assertMatchesRegularExpression('(/[0-9a-z]/foo-[0-9a-z]{15}(-\d)?\.png$)', $preview->getPath());
        }

        $lastPagePath = substr($previews[0]->getPath(), 0, -4).'-last.png';

        $this->assertFileDoesNotExist($lastPagePath);

        $previews = $factory->createPreviews($sourcePath, 200, 9999, 2);

        $this->assertCount(2, $previews);
        $this->assertFileExists($lastPagePath);

        $previews = $factory->createPreviews($sourcePath, 9999, 9999, 2);

        $this->assertCount(2, $previews);

        foreach ($previews as $preview) {
            $this->assertFileExists($preview->getPath());
            $this->assertSame(499, $preview->getDimensions()->getSize()->getWidth());
            $this->assertSame(998, $preview->getDimensions()->getSize()->getHeight());
            $this->assertMatchesRegularExpression('(/[0-9a-z]/foo-[0-9a-z]{15}-\d\.png$)', $preview->getPath());
        }

        $previews = $factory->createPreviews($sourcePath, 128, 9999, 4);

        $this->assertCount(0, $previews);

        (new Filesystem())->dumpFile($sourcePath, 'not a PDF');

        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreviews($sourcePath, 128);
    }

    /**
     * @dataProvider getImageSizes
     */
    public function testGetPreviewSizeFromImageSize(PictureConfiguration|ResizeConfiguration|array|int|string|null $size, int $expectedSize, string $defaultDensities = ''): void
    {
        $imageSizeModel = $this->mockClassWithProperties(ImageSizeModel::class);
        $imageSizeModel->setRow([
            'id' => 456,
            'width' => 20,
            'height' => 50,
            'densities' => '1x, 2x, 120w',
        ]);

        $imageSizeItemModel = $this->mockClassWithProperties(ImageSizeItemModel::class);
        $imageSizeItemModel->setRow([
            'pid' => 456,
            'width' => 789,
            'height' => 123,
            'densities' => '0.5x',
        ]);

        $imageSizeAdapter = $this->mockAdapter(['findByPk']);
        $imageSizeAdapter
            ->method('findByPk')
            ->willReturn($imageSizeModel)
        ;

        $imageSizeItemAdapter = $this->mockAdapter(['findVisibleByPid']);
        $imageSizeItemAdapter
            ->method('findVisibleByPid')
            ->willReturn([$imageSizeItemModel])
        ;

        $framework = $this->mockContaoFramework([
            ImageSizeModel::class => $imageSizeAdapter,
            ImageSizeItemModel::class => $imageSizeItemAdapter,
        ]);

        $factory = $this->createFactoryWithExampleProvider($framework);
        $factory->setDefaultDensities($defaultDensities);
        $factory->setPredefinedSizes([
            '_predefined' => [
                'width' => 20,
                'height' => 50,
                'densities' => '1x, 2x, 120w',
                'items' => [
                    [
                        'width' => 50,
                        'height' => 123,
                    ],
                ],
            ],
        ]);

        $this->assertSame($expectedSize, $factory->getPreviewSizeFromImageSize($size));

        if (\is_array($size)) {
            $this->assertSame($expectedSize, $factory->getPreviewSizeFromImageSize($size));
        }
    }

    public function getImageSizes(): \Generator
    {
        yield [null, 0];
        yield [[], 0];
        yield [[0, 0, 'crop'], 0];
        yield [[100, 0, 'crop'], 100];
        yield [[0, 100, 'crop'], 100];
        yield [[200, 100, 'crop'], 200];
        yield [[200, 100, 'box'], 200];
        yield [[200, 100, 'left_top'], 200];
        yield [[200, 100, 'crop'], 400, '2x'];
        yield [[200, 100, 'crop'], 300, '1.5x'];
        yield [[200, 100, 'crop'], 500, '500w'];
        yield [[200, 100, 'crop'], 240, '50w, 40w, 1.2x'];
        yield ['_predefined', 123];
        yield [[0, 0, '_predefined'], 123];
        yield [[500, 500, '_predefined'], 123];
        yield [456, 789];
        yield [[0, 0, 456], 789];
        yield [[500, 500, 456], 789];

        yield [(new ResizeConfiguration())->setWidth(123)->setHeight(456), 456];

        yield [
            (new PictureConfiguration())
                ->setSize(
                    (new PictureConfigurationItem())
                        ->setDensities('1.5x')
                        ->setResizeConfig((new ResizeConfiguration())->setWidth(123)->setHeight(456))
                ),
            684,
        ];

        yield [
            (new PictureConfiguration())
                ->setSize(
                    (new PictureConfigurationItem())
                        ->setDensities('1.5x')
                        ->setResizeConfig((new ResizeConfiguration())->setWidth(123)->setHeight(123))
                )
                ->setSizeItems([
                    (new PictureConfigurationItem())
                        ->setDensities('543w, 1.2x')
                        ->setResizeConfig((new ResizeConfiguration())->setWidth(100)->setHeight(150)),
                    (new PictureConfigurationItem())
                        ->setDensities('432w, 1.2x')
                        ->setResizeConfig((new ResizeConfiguration())->setWidth(100)->setHeight(150)),
                ]),
            543,
        ];
    }

    public function testCreatePreviewImage(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');

        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $factory = $this->createFactoryWithExampleProvider();
        $preview = $factory->createPreviewImage($sourcePath);

        $this->assertFileExists($preview->getPath());

        (new Filesystem())->dumpFile($sourcePath, 'not a PDF');

        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreviewImage($sourcePath, [200, 200, 'box']);
    }

    public function testCreatePreviewImages(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');

        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $factory = $this->createFactoryWithExampleProvider();
        $previews = $factory->createPreviewImages($sourcePath);

        foreach ($previews as $preview) {
            $this->assertFileExists($preview->getPath());
        }

        (new Filesystem())->dumpFile($sourcePath, 'not a PDF');

        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreviewImages($sourcePath, [200, 200, 'box']);
    }

    public function testCreatePreviewPicture(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');

        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $factory = $this->createFactoryWithExampleProvider();
        $preview = $factory->createPreviewPicture($sourcePath);

        $this->assertFileExists($preview->getImg()['src']->getPath());

        (new Filesystem())->dumpFile($sourcePath, 'not a PDF');

        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreviewPicture($sourcePath, [200, 200, 'box']);
    }

    public function testCreatePreviewPictures(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'sources/foo.pdf');

        (new Filesystem())->dumpFile($sourcePath, '%PDF-');

        $factory = $this->createFactoryWithExampleProvider();
        $previews = $factory->createPreviewPictures($sourcePath);

        foreach ($previews as $preview) {
            $this->assertFileExists($preview->getImg()['src']->getPath());
        }

        (new Filesystem())->dumpFile($sourcePath, 'not a PDF');

        $this->expectException(MissingPreviewProviderException::class);

        $factory->createPreviewPictures($sourcePath, [200, 200, 'box']);
    }

    private function createFactoryWithExampleProvider(ContaoFramework|null $framework = null): PreviewFactory
    {
        $pdfProvider = new class() implements PreviewProviderInterface {
            public function getFileHeaderSize(): int
            {
                return 5;
            }

            public function supports(string $path, string $fileHeader = '', array $options = []): bool
            {
                return str_ends_with($path, '.pdf') && str_starts_with($fileHeader, '%PDF-');
            }

            public function generatePreviews(string $sourcePath, int $size, \Closure $targetPathCallback, int $lastPage = PHP_INT_MAX, int $firstPage = 1, array $options = []): \Generator
            {
                $lastPage = min(3, $lastPage);

                for ($page = $firstPage; $page <= $lastPage; ++$page) {
                    (new Imagine())
                        ->create(new Box($size, $size * 2))
                        ->save($targetPath = $targetPathCallback($page).'.png')
                    ;

                    yield $targetPath;
                }
            }
        };

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->method('create')
            ->willReturnCallback(
                function ($path) {
                    if ($path instanceof ImageInterface) {
                        return $path;
                    }

                    return new Image($path, $this->createMock(ImagineInterface::class));
                }
            )
        ;

        $pictureFactory = $this->createMock(PictureFactoryInterface::class);
        $pictureFactory
            ->method('create')
            ->willReturnCallback(
                function ($path) {
                    if (!$path instanceof ImageInterface) {
                        $path = new Image($path, $this->createMock(ImagineInterface::class));
                    }

                    return new Picture(['src' => $path, 'srcset' => [[$path, '1x']]], []);
                }
            )
        ;

        return new PreviewFactory(
            [$pdfProvider],
            $imageFactory,
            $pictureFactory,
            $this->createMock(Studio::class),
            $framework ?? $this->createMock(ContaoFramework::class),
            'not so secret ;)',
            Path::join($this->getTempDir(), 'assets/previews'),
            ['png'],
            128,
            499,
        );
    }
}

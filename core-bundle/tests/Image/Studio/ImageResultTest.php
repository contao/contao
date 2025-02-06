<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Studio;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\DeferredImage;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Contao\Image\PictureInterface;
use Contao\Image\Resizer;
use Imagine\Image\ImagineInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ImageResultTest extends TestCase
{
    public function testGetPicture(): void
    {
        $filePathOrImage = '/project/dir/foo/bar/foobar.png';
        $sizeConfiguration = [100, 200, 'crop'];

        $picture = $this->createMock(PictureInterface::class);
        $pictureFactory = $this->mockPictureFactory($filePathOrImage, $sizeConfiguration, $picture);
        $locator = $this->mockLocator($pictureFactory);
        $imageResult = new ImageResult($locator, '/project/dir', $filePathOrImage, $sizeConfiguration);

        $this->assertSame($picture, $imageResult->getPicture());
    }

    public function testGetSourcesAndImg(): void
    {
        $filePathOrImage = '/project/dir/foo/bar/foobar.png';
        $sizeConfiguration = [100, 200, 'crop'];

        $projectDir = '/project/dir';
        $staticUrl = 'https://static.url';

        $sources = ['sources result'];
        $img = ['img result'];

        $picture = $this->createMock(PictureInterface::class);
        $picture
            ->expects($this->once())
            ->method('getSources')
            ->with($projectDir, $staticUrl)
            ->willReturn($sources)
        ;

        $picture
            ->expects($this->once())
            ->method('getImg')
            ->with($projectDir, $staticUrl)
            ->willReturn($img)
        ;

        $pictureFactory = $this->mockPictureFactory($filePathOrImage, $sizeConfiguration, $picture);
        $locator = $this->mockLocator($pictureFactory, $staticUrl);
        $imageResult = new ImageResult($locator, $projectDir, $filePathOrImage, $sizeConfiguration);

        $this->assertSame($sources, $imageResult->getSources());
        $this->assertSame($img, $imageResult->getImg());
    }

    public function testGetImageSrc(): void
    {
        $filePathOrImage = '/project/dir/foo/bar/foobar.png';
        $sizeConfiguration = [100, 200, 'crop'];

        $projectDir = '/project/dir';
        $staticUrl = 'https://static.url';

        $img = ['src' => 'https://static.url/foo/bar/foobar.png', 'other' => 'bar'];

        $picture = $this->createMock(PictureInterface::class);
        $picture
            ->expects($this->once())
            ->method('getImg')
            ->with($projectDir, $staticUrl)
            ->willReturn($img)
        ;

        $pictureFactory = $this->mockPictureFactory($filePathOrImage, $sizeConfiguration, $picture);
        $locator = $this->mockLocator($pictureFactory, $staticUrl);
        $imageResult = new ImageResult($locator, $projectDir, $filePathOrImage, $sizeConfiguration);

        $this->assertSame('https://static.url/foo/bar/foobar.png', $imageResult->getImageSrc());
    }

    public function testGetImageSrcAsPath(): void
    {
        $filePathOrImage = '/project/dir/foo/bar/foobar.png';
        $sizeConfiguration = [100, 200, 'crop'];

        $projectDir = '/project/dir';
        $staticUrl = 'https://static.url';

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->willReturn(true)
        ;

        $img = [
            'src' => new Image(
                $filePathOrImage,
                $this->createMock(ImagineInterface::class),
                $filesystem,
            ),
        ];

        $picture = $this->createMock(PictureInterface::class);
        $picture
            ->expects($this->once())
            ->method('getImg')
            ->with()
            ->willReturn($img)
        ;

        $pictureFactory = $this->mockPictureFactory($filePathOrImage, $sizeConfiguration, $picture);
        $locator = $this->mockLocator($pictureFactory, $staticUrl);
        $imageResult = new ImageResult($locator, $projectDir, $filePathOrImage, $sizeConfiguration);

        $this->assertSame('foo/bar/foobar.png', $imageResult->getImageSrc(true));
    }

    public function testGetOriginalDimensionsFromPathResource(): void
    {
        $filePathOrImage = '/project/dir/foo/bar/foobar.png';
        $dimensions = $this->createMock(ImageDimensions::class);

        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getDimensions')
            ->willReturn($dimensions)
        ;

        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->once())
            ->method('create')
            ->with($filePathOrImage)
            ->willReturn($image)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with('contao.image.factory')
            ->willReturn($imageFactory)
        ;

        $imageResult = new ImageResult($locator, '/project/dir', $filePathOrImage);

        $this->assertSame($dimensions, $imageResult->getOriginalDimensions());

        // Expect result to be cached on second call
        $imageResult->getOriginalDimensions();
    }

    public function testGetOriginalDimensionsFromImageResource(): void
    {
        $dimensions = $this->createMock(ImageDimensions::class);

        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getDimensions')
            ->willReturn($dimensions)
        ;

        $locator = $this->mockLocator();
        $imageResult = new ImageResult($locator, 'any/project/dir', $image);

        $this->assertSame($dimensions, $imageResult->getOriginalDimensions());
    }

    public function testGetFilePathFromPathResource(): void
    {
        $projectDir = 'project/dir';
        $filePath = 'project/dir/file/path';

        $locator = $this->mockLocator();
        $imageResult = new ImageResult($locator, $projectDir, $filePath);

        $this->assertSame('file/path', $imageResult->getFilePath());
        $this->assertSame('project/dir/file/path', $imageResult->getFilePath(true));
    }

    public function testGetFilePathFromImageResource(): void
    {
        $projectDir = 'project/dir';
        $filePath = 'project/dir/file/path';

        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn($filePath)
        ;

        $locator = $this->mockLocator();
        $imageResult = new ImageResult($locator, $projectDir, $image);

        $this->assertSame('file/path', $imageResult->getFilePath());
        $this->assertSame('project/dir/file/path', $imageResult->getFilePath(true));
    }

    #[DataProvider('provideDeferredImages')]
    public function testCreateIfDeferred(\Closure $parametersDelegate): void
    {
        [$img, $sources, $expectedDeferredImages] = $parametersDelegate->bindTo($this)();

        $picture = $this->createMock(PictureInterface::class);
        $picture
            ->expects($this->once())
            ->method('getSources')
            ->with()
            ->willReturn($sources)
        ;

        $picture
            ->expects($this->once())
            ->method('getImg')
            ->with()
            ->willReturn($img)
        ;

        $pictureFactory = $this->createMock(PictureFactoryInterface::class);
        $pictureFactory
            ->method('create')
            ->willReturn($picture)
        ;

        $deferredResizer = $this->createMock(DeferredResizerInterface::class);
        $deferredResizer
            ->expects($expectedDeferredImages ? $this->atLeast(\count($expectedDeferredImages)) : $this->never())
            ->method('resizeDeferredImage')
            ->with($this->callback(
                static function ($deferredImage) use (&$expectedDeferredImages) {
                    foreach ($expectedDeferredImages as $key => $expectedDeferredImage) {
                        if ($expectedDeferredImage->getPath() === $deferredImage->getPath()) {
                            unset($expectedDeferredImages[$key]);
                        }
                    }

                    return true;
                },
            ))
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->method('get')
            ->willReturnMap([
                ['contao.image.picture_factory', $pictureFactory],
                ['contao.image.resizer', $deferredResizer],
            ])
        ;

        $imageResult = new ImageResult($locator, '/project/dir', '/project/dir/image.jpg');
        $imageResult->createIfDeferred();

        $this->assertEmpty($expectedDeferredImages, 'test all images were processed');
    }

    public static function provideDeferredImages(): iterable
    {
        $imagine = fn () => $this->createMock(ImagineInterface::class);
        $dimensions = fn () => $this->createMock(ImageDimensions::class);

        $filesystem = function () {
            $filesystem = $this->createMock(Filesystem::class);
            $filesystem
                ->method('exists')
                ->willReturn(true)
            ;

            return $filesystem;
        };

        $image = fn () => new Image('/project/dir/assets/image0.jpg', $imagine->bindTo($this)(), $filesystem->bindTo($this)());
        $deferredImage1 = fn () => new DeferredImage('/project/dir/assets/image1.jpg', $imagine->bindTo($this)(), $dimensions->bindTo($this)());
        $deferredImage2 = fn () => new DeferredImage('/project/dir/assets/image2.jpg', $imagine->bindTo($this)(), $dimensions->bindTo($this)());
        $deferredImage3 = fn () => new DeferredImage('/project/dir/assets/image3.jpg', $imagine->bindTo($this)(), $dimensions->bindTo($this)());
        $deferredImage4 = fn () => new DeferredImage('/project/dir/assets/image4.jpg', $imagine->bindTo($this)(), $dimensions->bindTo($this)());

        yield 'no deferred images' => [
            fn () => [
                ['src' => $image->bindTo($this)()],
                [],
                [],
            ],
        ];

        yield 'img and sources with deferred images' => [
            fn () => [
                [
                    'src' => $deferredImage1->bindTo($this)(),
                    'srcset' => [[$deferredImage2->bindTo($this)(), 'foo'], [$deferredImage3->bindTo($this)()]],
                ],
                [
                    [
                        'src' => $deferredImage3->bindTo($this)(),
                        'srcset' => [[$deferredImage2->bindTo($this)()], [$deferredImage4->bindTo($this)()]],
                    ],
                    [
                        'src' => $deferredImage2->bindTo($this)(),
                        'srcset' => [[$deferredImage4->bindTo($this)()]],
                    ],
                ],
                [$deferredImage1->bindTo($this)(), $deferredImage2->bindTo($this)(), $deferredImage3->bindTo($this)(), $deferredImage4->bindTo($this)()],
            ],
        ];

        yield 'img and sources with both deferred and non-deferred images' => [
            fn () => [
                [
                    'src' => $deferredImage1->bindTo($this)(),
                ],
                [
                    [
                        'src' => $image,
                    ],
                    [
                        'src' => $deferredImage2->bindTo($this)(),
                        'srcset' => [[$deferredImage3->bindTo($this)()]],
                    ],
                ],
                [$deferredImage1->bindTo($this)(), $deferredImage2->bindTo($this)(), $deferredImage3->bindTo($this)()],
            ],
        ];

        yield 'elements without src or srcset key' => [
            fn () => [
                [
                    'foo' => 'bar',
                ],
                [
                    [
                        'bar' => 'foo',
                    ],
                    [
                        'srcset' => [['foo'], [$deferredImage2->bindTo($this)()]],
                    ],
                    [
                        'src' => $deferredImage1->bindTo($this)(),
                    ],
                ],
                [$deferredImage1->bindTo($this)(), $deferredImage2->bindTo($this)()],
            ],
        ];
    }

    public function testCreateIfDeferredFailsWithoutDeferredResizer(): void
    {
        $picture = $this->createMock(PictureInterface::class);
        $picture
            ->expects($this->once())
            ->method('getSources')
            ->with()
            ->willReturn([])
        ;

        $picture
            ->expects($this->once())
            ->method('getImg')
            ->with()
            ->willReturn(['src' => $this->createMock(DeferredImageInterface::class)])
        ;

        $pictureFactory = $this->createMock(PictureFactoryInterface::class);
        $pictureFactory
            ->method('create')
            ->willReturn($picture)
        ;

        $nonDeferredResizer = $this->createMock(Resizer::class);

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->method('get')
            ->willReturnMap([
                ['contao.image.picture_factory', $pictureFactory],
                ['contao.image.resizer', $nonDeferredResizer],
            ])
        ;

        $imageResult = new ImageResult($locator, '/project/dir', '/project/dir/image.jpg');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "contao.image.resizer" service does not support deferred resizing.');

        $imageResult->createIfDeferred();
    }

    public function testCreateIfDeferredDoesNotFailWithoutDeferredResizerIfThereAreNoDeferredImages(): void
    {
        $picture = $this->createMock(PictureInterface::class);
        $picture
            ->expects($this->once())
            ->method('getSources')
            ->with()
            ->willReturn([])
        ;

        $picture
            ->expects($this->once())
            ->method('getImg')
            ->with()
            ->willReturn([])
        ;

        $pictureFactory = $this->createMock(PictureFactoryInterface::class);
        $pictureFactory
            ->method('create')
            ->willReturn($picture)
        ;

        $nonDeferredResizer = $this->createMock(Resizer::class);

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->method('get')
            ->willReturnMap([
                ['contao.image.picture_factory', $pictureFactory],
                ['contao.image.resizer', $nonDeferredResizer],
            ])
        ;

        $imageResult = new ImageResult($locator, '/project/dir', '/project/dir/image.jpg');
        $imageResult->createIfDeferred();
    }

    private function mockPictureFactory(string $filePathOrImage, array $sizeConfiguration, PictureInterface $picture): PictureFactoryInterface&MockObject
    {
        $pictureFactory = $this->createMock(PictureFactoryInterface::class);
        $pictureFactory
            ->expects($this->once())
            ->method('create')
            ->with($filePathOrImage, $sizeConfiguration)
            ->willReturn($picture)
        ;

        return $pictureFactory;
    }

    private function mockLocator(PictureFactoryInterface|null $pictureFactory = null, string|null $staticUrl = null): ContainerInterface&MockObject
    {
        $locator = $this->createMock(ContainerInterface::class);
        $context = null;

        if (null !== $staticUrl) {
            $context = $this->createMock(ContaoContext::class);
            $context
                ->method('getStaticUrl')
                ->willReturn($staticUrl)
            ;
        }

        $locator
            ->method('get')
            ->willReturnMap([
                ['contao.image.picture_factory', $pictureFactory],
                ['contao.assets.files_context', $context],
            ])
        ;

        return $locator;
    }
}

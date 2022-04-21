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
                $filesystem
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

    /**
     * @dataProvider provideDeferredImages
     */
    public function testCreateIfDeferred(array $img, array $sources, array $expectedDeferredImages): void
    {
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
            ->expects(empty($expectedDeferredImages) ? $this->never() : $this->atLeast(\count($expectedDeferredImages)))
            ->method('resizeDeferredImage')
            ->with($this->callback(
                static function ($deferredImage) use (&$expectedDeferredImages) {
                    if (false !== ($key = array_search($deferredImage, $expectedDeferredImages, true))) {
                        unset($expectedDeferredImages[$key]);
                    }

                    return true;
                }
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

    public function provideDeferredImages(): \Generator
    {
        $imagine = $this->createMock(ImagineInterface::class);
        $dimensions = $this->createMock(ImageDimensions::class);

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem
            ->method('exists')
            ->willReturn(true)
        ;

        $image = new Image('/project/dir/assets/image0.jpg', $imagine, $filesystem);
        $deferredImage1 = new DeferredImage('/project/dir/assets/image1.jpg', $imagine, $dimensions);
        $deferredImage2 = new DeferredImage('/project/dir/assets/image2.jpg', $imagine, $dimensions);
        $deferredImage3 = new DeferredImage('/project/dir/assets/image3.jpg', $imagine, $dimensions);
        $deferredImage4 = new DeferredImage('/project/dir/assets/image4.jpg', $imagine, $dimensions);

        yield 'no deferred images' => [
            ['src' => $image],
            [],
            [],
        ];

        yield 'img and sources with deferred images' => [
            [
                'src' => $deferredImage1,
                'srcset' => [[$deferredImage2, 'foo'], [$deferredImage3]],
            ],
            [
                [
                    'src' => $deferredImage3,
                    'srcset' => [[$deferredImage2], [$deferredImage4]],
                ],
                [
                    'src' => $deferredImage2,
                    'srcset' => [[$deferredImage4]],
                ],
            ],
            [$deferredImage1, $deferredImage2, $deferredImage3, $deferredImage4],
        ];

        yield 'img and sources with both deferred and non-deferred images' => [
            [
                'src' => $deferredImage1,
            ],
            [
                [
                    'src' => $image,
                ],
                [
                    'src' => $deferredImage2,
                    'srcset' => [[$deferredImage3]],
                ],
            ],
            [$deferredImage1, $deferredImage2, $deferredImage3],
        ];

        yield 'elements without src or srcset key' => [
            [
                'foo' => 'bar',
            ],
            [
                [
                    'bar' => 'foo',
                ],
                [
                    'srcset' => [['foo'], [$deferredImage2]],
                ],
                [
                    'src' => $deferredImage1,
                ],
            ],
            [$deferredImage1, $deferredImage2],
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

    /**
     * @return PictureFactoryInterface&MockObject
     */
    private function mockPictureFactory(string $filePathOrImage, array $sizeConfiguration, PictureInterface $picture): PictureFactoryInterface
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

    /**
     * @return ContainerInterface&MockObject
     */
    private function mockLocator(PictureFactoryInterface $pictureFactory = null, string $staticUrl = null): ContainerInterface
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

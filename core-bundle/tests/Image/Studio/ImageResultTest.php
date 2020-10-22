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
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Tests\Fixtures\Image\PictureFactoryWithoutResizeOptionsStub;
use Contao\CoreBundle\Tests\Fixtures\Image\PictureFactoryWithRandomArgumentStub;
use Contao\CoreBundle\Tests\Fixtures\Image\PictureFactoryWithResizeOptionsStub;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Contao\Image\PictureInterface;
use Contao\Image\ResizeOptions;
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

        /** @var PictureInterface&MockObject $picture */
        $picture = $this->createMock(PictureInterface::class);
        $pictureFactory = $this->getPictureFactoryMock($filePathOrImage, $sizeConfiguration, $picture);
        $locator = $this->getLocatorMock($pictureFactory);
        $imageResult = new ImageResult($locator, '/project/dir', $filePathOrImage, $sizeConfiguration);

        $this->assertSame($picture, $imageResult->getPicture());
    }

    /**
     * @dataProvider providePictureFactories
     */
    public function testGetPictureWithResizeMode(string $pictureFactory, bool $supportsResizeOptions): void
    {
        $resizeOptions = new ResizeOptions();

        /** @var PictureFactoryInterface&MockObject $pictureFactory */
        $pictureFactory = $this->createMock($pictureFactory);
        $pictureFactory
            ->expects($this->once())
            ->method('create')
            ->willReturnCallback(
                function () use ($supportsResizeOptions, $resizeOptions) {
                    // Expect factories with a compatible 'create' method signature
                    // to be called with $resizeOptions as 3rd argument.
                    if ($supportsResizeOptions) {
                        $this->assertSame($resizeOptions, func_get_arg(2));
                    } else {
                        $this->assertNull(\func_get_args()[2] ?? null);
                    }

                    return $this->createMock(PictureInterface::class);
                }
            )
        ;

        $imageResult = new ImageResult(
            $this->getLocatorMock($pictureFactory),
            'any/project/dir',
            'foo/bar/foobar.png',
            [100, 200, 'crop'],
            $resizeOptions
        );

        $imageResult->getPicture();
    }

    public function providePictureFactories(): \Generator
    {
        yield 'Contao default picture factory' => [
            PictureFactory::class,
            true,
        ];

        yield 'Custom picture factory with resize options' => [
            PictureFactoryWithResizeOptionsStub::class,
            true,
        ];

        yield 'Custom picture factory without resize options' => [
            PictureFactoryWithoutResizeOptionsStub::class,
            false,
        ];

        yield 'Custom picture factory with random options' => [
            PictureFactoryWithRandomArgumentStub::class,
            false,
        ];
    }

    public function testGetSourcesAndImg(): void
    {
        $filePathOrImage = '/project/dir/foo/bar/foobar.png';
        $sizeConfiguration = [100, 200, 'crop'];

        $projectDir = '/project/dir';
        $staticUrl = 'https://static.url';

        $sources = ['sources result'];
        $img = ['img result'];

        /** @var PictureInterface&MockObject $picture */
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

        $pictureFactory = $this->getPictureFactoryMock($filePathOrImage, $sizeConfiguration, $picture);
        $locator = $this->getLocatorMock($pictureFactory, $staticUrl);
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

        /** @var PictureInterface&MockObject $picture */
        $picture = $this->createMock(PictureInterface::class);
        $picture
            ->expects($this->once())
            ->method('getImg')
            ->with($projectDir, $staticUrl)
            ->willReturn($img)
        ;

        $pictureFactory = $this->getPictureFactoryMock($filePathOrImage, $sizeConfiguration, $picture);
        $locator = $this->getLocatorMock($pictureFactory, $staticUrl);
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

        /** @var PictureInterface&MockObject $picture */
        $picture = $this->createMock(PictureInterface::class);
        $picture
            ->expects($this->once())
            ->method('getImg')
            ->with()
            ->willReturn($img)
        ;

        $pictureFactory = $this->getPictureFactoryMock($filePathOrImage, $sizeConfiguration, $picture);
        $locator = $this->getLocatorMock($pictureFactory, $staticUrl);
        $imageResult = new ImageResult($locator, $projectDir, $filePathOrImage, $sizeConfiguration);

        $this->assertSame('foo/bar/foobar.png', $imageResult->getImageSrc(true));
    }

    public function testGetOriginalDimensionsFromPathResource(): void
    {
        $filePathOrImage = '/project/dir/foo/bar/foobar.png';
        $dimensions = $this->createMock(ImageDimensions::class);

        /** @var ImageInterface&MockObject $image */
        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getDimensions')
            ->willReturn($dimensions)
        ;

        /** @var ImageFactoryInterface&MockObject $imageFactory */
        $imageFactory = $this->createMock(ImageFactoryInterface::class);
        $imageFactory
            ->expects($this->once())
            ->method('create')
            ->with($filePathOrImage)
            ->willReturn($image)
        ;

        /** @var ContainerInterface&MockObject $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with('contao.image.image_factory')
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

        /** @var ImageInterface&MockObject $image */
        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->once())
            ->method('getDimensions')
            ->willReturn($dimensions)
        ;

        $locator = $this->getLocatorMock();
        $imageResult = new ImageResult($locator, 'any/project/dir', $image);

        $this->assertSame($dimensions, $imageResult->getOriginalDimensions());
    }

    public function testGetFilePathFromPathResource(): void
    {
        $projectDir = 'project/dir';
        $filePath = 'project/dir/file/path';

        $locator = $this->getLocatorMock(null);
        $imageResult = new ImageResult($locator, $projectDir, $filePath);

        $this->assertSame('file/path', $imageResult->getFilePath());
        $this->assertSame('project/dir/file/path', $imageResult->getFilePath(true));
    }

    public function testGetFilePathFromImageResource(): void
    {
        $projectDir = 'project/dir';
        $filePath = 'project/dir/file/path';

        /** @var ImageInterface&MockObject $image */
        $image = $this->createMock(ImageInterface::class);
        $image
            ->expects($this->exactly(2))
            ->method('getPath')
            ->willReturn($filePath)
        ;

        $locator = $this->getLocatorMock();
        $imageResult = new ImageResult($locator, $projectDir, $image);

        $this->assertSame('file/path', $imageResult->getFilePath());
        $this->assertSame('project/dir/file/path', $imageResult->getFilePath(true));
    }

    /**
     * @return PictureFactoryInterface&MockObject
     */
    private function getPictureFactoryMock($filePathOrImage, $sizeConfiguration, PictureInterface $picture)
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
    private function getLocatorMock(?PictureFactoryInterface $pictureFactory = null, string $staticUrl = null)
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

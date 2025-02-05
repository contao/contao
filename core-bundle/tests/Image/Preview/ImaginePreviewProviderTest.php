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

use Contao\CoreBundle\Image\Preview\FallbackPreviewProvider;
use Contao\CoreBundle\Image\Preview\ImaginePreviewProvider;
use Contao\CoreBundle\Image\Preview\UnableToGeneratePreviewException;
use Contao\CoreBundle\Tests\TestCase;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\LayersInterface;
use Symfony\Component\Filesystem\Path;

class ImaginePreviewProviderTest extends TestCase
{
    public function testSupports(): void
    {
        $basePath = Path::join($this->getTempDir(), 'foo/bar');
        $provider = $this->createProvider();

        $this->assertTrue($provider->supports("$basePath.jpeg"));
        $this->assertTrue($provider->supports("$basePath.gif"));
        $this->assertTrue($provider->supports("$basePath.png"));
        $this->assertFalse($provider->supports("$basePath.exe"));
    }

    public function testGetFileHeaderSize(): void
    {
        $this->assertSame(0, (new FallbackPreviewProvider())->getFileHeaderSize());
    }

    public function testGeneratePreviews(): void
    {
        $sourcePath = Path::join($this->getTempDir(), 'foo/bar.txt');
        $targetPath = Path::join($this->getTempDir(), 'assets/images/previews/bar');

        $targetPathCallback = static fn (int $page): string => $targetPath.$page;

        $layers = $this->createMock(LayersInterface::class);

        $image = $this->createMock(ImageInterface::class);
        $image
            ->method('layers')
            ->willReturn($layers)
        ;

        $image
            ->method('getSize')
            ->willReturn(new Box(1024, 1024))
        ;

        $image
            ->expects($this->exactly(2))
            ->method('resize')
            ->with(new Box(512, 512))
            ->willReturnSelf()
        ;

        $image
            ->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(["{$targetPath}1.png"], ["{$targetPath}2.png"])
            ->willReturnSelf()
        ;

        $layers
            ->method('has')
            ->withConsecutive([0], [1])
            ->willReturn(true)
        ;

        $layers
            ->method('get')
            ->withConsecutive([0], [1])
            ->willReturn($image)
        ;

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->expects($this->once())
            ->method('open')
            ->with($sourcePath)
            ->willReturn($image)
        ;

        $provider = $this->createProvider($imagine);

        $this->assertSame(
            ["{$targetPath}1.png", "{$targetPath}2.png"],
            $provider->generatePreviews($sourcePath, 512, $targetPathCallback, 2),
        );

        $imagine = $this->createMock(ImagineInterface::class);
        $imagine
            ->expects($this->once())
            ->method('open')
            ->with($sourcePath)
            ->willThrowException(new \RuntimeException('Exception from Imagine'))
        ;

        $provider = $this->createProvider($imagine);

        $this->expectException(UnableToGeneratePreviewException::class);

        $provider->generatePreviews($sourcePath, 512, $targetPathCallback);
    }

    private function createProvider(ImagineInterface|null $imagine = null): ImaginePreviewProvider
    {
        return new ImaginePreviewProvider($imagine ?? new Imagine());
    }
}

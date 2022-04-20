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

use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\LightboxResult;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeOptions;
use Contao\LayoutModel;
use Contao\PageModel;
use Psr\Container\ContainerInterface;

class LightboxResultTest extends TestCase
{
    /**
     * @dataProvider provideInvalidConfigurations
     */
    public function testCanOnlyBeConstructedWithEitherAResourceOrAnUrl(ImageInterface|string|null $resource, string|null $url): void
    {
        $locator = $this->createMock(ContainerInterface::class);

        $this->expectException(\InvalidArgumentException::class);

        new LightboxResult($locator, $resource, $url);
    }

    public function provideInvalidConfigurations(): \Generator
    {
        yield 'both empty' => [null, null];

        yield 'both set' => ['foo', 'bar'];
    }

    public function testUsesFallBackLightboxSizeConfiguration(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];
        $layoutId = 1;

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->lightboxSize = serialize($size);

        $layoutModelAdapter = $this->mockAdapter(['findByPk']);
        $layoutModelAdapter
            ->method('findByPk')
            ->with($layoutId)
            ->willReturn($layoutModel)
        ;

        $framework = $this->mockContaoFramework([LayoutModel::class => $layoutModelAdapter]);

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->layout = $layoutId;

        $GLOBALS['objPage'] = $pageModel;

        $image = $this->createMock(ImageResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size)
            ->willReturn($image)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['contao.framework', $framework],
                ['contao.image.studio', $studio],
            ])
        ;

        new LightboxResult($locator, $resource, null);

        unset($GLOBALS['objPage']);
    }

    public function testFallBackLightboxSizeConfigurationFailsIfNoLightboxSizeSet(): void
    {
        $resource = 'foo/bar.png';
        $layoutId = 1;

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->lightboxSize = '';

        $layoutModelAdapter = $this->mockAdapter(['findByPk']);
        $layoutModelAdapter
            ->method('findByPk')
            ->with($layoutId)
            ->willReturn($layoutModel)
        ;

        $framework = $this->mockContaoFramework([LayoutModel::class => $layoutModelAdapter]);

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->layout = $layoutId;

        $GLOBALS['objPage'] = $pageModel;

        $image = $this->createMock(ImageResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, null)
            ->willReturn($image)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['contao.framework', $framework],
                ['contao.image.studio', $studio],
            ])
        ;

        new LightboxResult($locator, $resource, null);

        unset($GLOBALS['objPage']);
    }

    public function testFallBackLightboxSizeConfigurationFailsIfNoPage(): void
    {
        $resource = 'foo/bar.png';
        $framework = $this->mockContaoFramework();
        $image = $this->createMock(ImageResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, null)
            ->willReturn($image)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->willReturnMap([
                ['contao.framework', $framework],
                ['contao.image.studio', $studio],
            ])
        ;

        // Note: $GLOBALS['objPage'] is not set at this point
        new LightboxResult($locator, $resource, null);
    }

    public function testHasImage(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];
        $image = $this->createMock(ImageResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size)
            ->willReturn($image)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with('contao.image.studio')
            ->willReturn($studio)
        ;

        $lightboxResult = new LightboxResult($locator, $resource, null, $size);

        $this->assertTrue($lightboxResult->hasImage());
    }

    public function testHasNoImage(): void
    {
        $locator = $this->createMock(ContainerInterface::class);
        $lightboxResult = new LightboxResult($locator, null, 'foo://bar');

        $this->assertFalse($lightboxResult->hasImage());
    }

    public function testGetImage(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];
        $image = $this->createMock(ImageResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size)
            ->willReturn($image)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with('contao.image.studio')
            ->willReturn($studio)
        ;

        $lightboxResult = new LightboxResult($locator, $resource, null, $size);

        $this->assertSame($image, $lightboxResult->getImage());
    }

    public function testGetImageThrowsIfNoImageWasSet(): void
    {
        $locator = $this->createMock(ContainerInterface::class);
        $lightboxResult = new LightboxResult($locator, null, 'foo://bar');

        $this->expectException(\RuntimeException::class);

        $lightboxResult->getImage();
    }

    public function testGetLinkHrefForImageResource(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];

        $image = $this->createMock(ImageResult::class);
        $image
            ->expects($this->once())
            ->method('getImageSrc')
            ->willReturn('foobar.png')
        ;

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size)
            ->willReturn($image)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with('contao.image.studio')
            ->willReturn($studio)
        ;

        $lightboxResult = new LightboxResult($locator, $resource, null, $size);

        $this->assertSame('foobar.png', $lightboxResult->getLinkHref());
    }

    public function testGetLinkHrefForUrl(): void
    {
        $locator = $this->createMock(ContainerInterface::class);
        $lightboxResult = new LightboxResult($locator, null, 'foo://bar');

        $this->assertSame('foo://bar', $lightboxResult->getLinkHref());
    }

    public function testGetGroupIdentifierIfExplicitlySet(): void
    {
        $locator = $this->createMock(ContainerInterface::class);
        $lightboxResult = new LightboxResult($locator, null, 'foo://bar', null, '12345');

        $this->assertSame('12345', $lightboxResult->getGroupIdentifier());
    }

    public function testGroupIdentifierIsEmptyIfNotExplicitlySet(): void
    {
        $locator = $this->createMock(ContainerInterface::class);
        $lightboxResult = new LightboxResult($locator, null, 'foo://bar');

        $this->assertSame('', $lightboxResult->getGroupIdentifier());
    }

    public function testPassesOnResizeOptions(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];
        $resizeOptions = new ResizeOptions();
        $image = $this->createMock(ImageResult::class);

        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size, $resizeOptions)
            ->willReturn($image)
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with('contao.image.studio')
            ->willReturn($studio)
        ;

        $lightboxResult = new LightboxResult($locator, $resource, null, $size, null, $resizeOptions);

        $this->assertSame($image, $lightboxResult->getImage());
    }
}

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
use Contao\CoreBundle\Image\Studio\LightBoxResult;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\LayoutModel;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class LightBoxResultTest extends TestCase
{
    /**
     * @dataProvider provideInvalidConfigurations
     */
    public function testCanOnlyBeConstructedWithEitherAResourceOrAnUrl($resource, ?string $url): void
    {
        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);

        $this->expectException(\InvalidArgumentException::class);

        new LightBoxResult($locator, $resource, $url);
    }

    public function provideInvalidConfigurations(): \Generator
    {
        yield 'both empty' => [null, null];

        yield 'both set' => ['foo', 'bar'];
    }

    public function testUsesFallBackLightBoxSizeConfiguration(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];
        $layoutId = '1';

        /** @var MockObject&LayoutModel $layoutModel */
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->lightboxSize = serialize($size);

        $layoutModelAdapter = $this->mockAdapter(['findByPk']);
        $layoutModelAdapter
            ->method('findByPk')
            ->with($layoutId)
            ->willReturn($layoutModel)
        ;

        $framework = $this->mockContaoFramework([LayoutModel::class => $layoutModelAdapter]);

        /** @var MockObject&PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->layout = $layoutId;

        $GLOBALS['objPage'] = $pageModel;

        /** @var MockObject&ImageResult $image */
        $image = $this->createMock(ImageResult::class);

        /** @var MockObject&Studio $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size)
            ->willReturn($image)
        ;

        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['contao.framework', $framework],
                [Studio::class, $studio],
            ])
        ;

        new LightBoxResult($locator, $resource, null);

        unset($GLOBALS['objPage']);
    }

    public function testFallBackLightBoxSizeConfigurationFailsIfNoLightBoxSizeSet(): void
    {
        $resource = 'foo/bar.png';
        $layoutId = '1';

        /** @var MockObject&LayoutModel $layoutModel */
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->lightboxSize = '';

        $layoutModelAdapter = $this->mockAdapter(['findByPk']);
        $layoutModelAdapter
            ->method('findByPk')
            ->with($layoutId)
            ->willReturn($layoutModel)
        ;

        $framework = $this->mockContaoFramework([LayoutModel::class => $layoutModelAdapter]);

        /** @var MockObject&PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->layout = $layoutId;

        $GLOBALS['objPage'] = $pageModel;

        /** @var MockObject&ImageResult $image */
        $image = $this->createMock(ImageResult::class);

        /** @var MockObject&Studio $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, null)
            ->willReturn($image)
        ;

        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['contao.framework', $framework],
                [Studio::class, $studio],
            ])
        ;

        new LightBoxResult($locator, $resource, null);

        unset($GLOBALS['objPage']);
    }

    public function testFallBackLightBoxSizeConfigurationFailsIfNoPage(): void
    {
        $resource = 'foo/bar.png';
        $framework = $this->mockContaoFramework();

        /** @var MockObject&ImageResult $image */
        $image = $this->createMock(ImageResult::class);

        /** @var MockObject&Studio $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, null)
            ->willReturn($image)
        ;

        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['contao.framework', $framework],
                [Studio::class, $studio],
            ])
        ;

        // Note: $GLOBALS['objPage'] is not set at this point
        new LightBoxResult($locator, $resource, null);
    }

    public function testHasImage(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];

        /** @var MockObject&Studio ImageResult */
        $image = $this->createMock(ImageResult::class);

        /** @var MockObject&Studio $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size)
            ->willReturn($image)
        ;

        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with(Studio::class)
            ->willReturn($studio)
        ;

        $lightBoxResult = new LightBoxResult($locator, $resource, null, $size);

        $this->assertTrue($lightBoxResult->hasImage());
    }

    public function testHasNoImage(): void
    {
        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $lightBoxResult = new LightBoxResult($locator, null, 'foo://bar');

        $this->assertFalse($lightBoxResult->hasImage());
    }

    public function testGetImage(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];

        /** @var MockObject&ImageResult $image */
        $image = $this->createMock(ImageResult::class);

        /** @var MockObject&Studio $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size)
            ->willReturn($image)
        ;

        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with(Studio::class)
            ->willReturn($studio)
        ;

        $lightBoxResult = new LightBoxResult($locator, $resource, null, $size);

        $this->assertSame($image, $lightBoxResult->getImage());
    }

    public function testGetImageThrowsIfNoImageWasSet(): void
    {
        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $lightBoxResult = new LightBoxResult($locator, null, 'foo://bar');

        $this->expectException(\RuntimeException::class);

        $lightBoxResult->getImage();
    }

    public function testGetLinkHrefForImageResource(): void
    {
        $resource = 'foo/bar.png';
        $size = [100, 200, 'crop'];

        /** @var MockObject&ImageResult $image */
        $image = $this->createMock(ImageResult::class);
        $image
            ->expects($this->once())
            ->method('getImageSrc')
            ->willReturn('foobar.png')
        ;

        /** @var MockObject&Studio $studio */
        $studio = $this->createMock(Studio::class);
        $studio
            ->expects($this->once())
            ->method('createImage')
            ->with($resource, $size)
            ->willReturn($image)
        ;

        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with(Studio::class)
            ->willReturn($studio)
        ;

        $lightBoxResult = new LightBoxResult($locator, $resource, null, $size);

        $this->assertSame('foobar.png', $lightBoxResult->getLinkHref());
    }

    public function testGetLinkHrefForUrl(): void
    {
        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $lightBoxResult = new LightBoxResult($locator, null, 'foo://bar');

        $this->assertSame('foo://bar', $lightBoxResult->getLinkHref());
    }

    public function testGetGroupIdentifierIfExplicitlySet(): void
    {
        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $lightBoxResult = new LightBoxResult($locator, null, 'foo://bar', null, '12345');

        $this->assertSame('12345', $lightBoxResult->getGroupIdentifier());
    }

    public function testGroupIdentifierIsEmptyIfNotExplicitlySet(): void
    {
        /** @var MockObject&ContainerInterface $locator */
        $locator = $this->createMock(ContainerInterface::class);
        $lightBoxResult = new LightBoxResult($locator, null, 'foo://bar');

        $this->assertSame('', $lightBoxResult->getGroupIdentifier());
    }
}

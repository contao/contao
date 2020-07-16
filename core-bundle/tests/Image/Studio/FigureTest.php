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

use Contao\CoreBundle\File\MetaData;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\LightBoxResult;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendTemplate;
use Contao\Image\ImageDimensions;
use Contao\System;
use Imagine\Image\BoxInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Webmozart\PathUtil\Path;

class FigureTest extends TestCase
{
    public function testGetImage(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->assertSame($image, $figure->getImage());
    }

    public function testHasNoLightBoxOrMetaDataByDefault(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->assertFalse($figure->hasLightBox());
        $this->assertFalse($figure->hasMetaData());
    }

    public function testGetLightBox(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);

        /** @var LightBoxResult&MockObject $lightBox */
        $lightBox = $this->createMock(LightBoxResult::class);
        $figure = new Figure($image, null, null, $lightBox);

        $this->assertTrue($figure->hasLightBox());
        $this->assertSame($lightBox, $figure->getLightBox());
    }

    public function testGetLightBoxSetViaCallback(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);

        /** @var LightBoxResult&MockObject $lightBox */
        $lightBox = $this->createMock(LightBoxResult::class);
        $called = 0;

        $lightBoxClosure = function (Figure $figure) use (&$called, $lightBox): LightBoxResult {
            $this->assertInstanceOf(Figure::class, $figure);
            ++$called;

            return $lightBox;
        };

        $figure = new Figure($image, null, null, $lightBoxClosure);

        $this->assertTrue($figure->hasLightBox());
        $this->assertSame($lightBox, $figure->getLightBox());

        $figure->getLightBox(); // second call should be cached
        $this->assertSame(1, $called);
    }

    public function testGetLightBoxFailsIfNotSet(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->expectException(\LogicException::class);

        $figure->getLightBox();
    }

    public function testGetMetaData(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $metaData = new MetaData(['foo' => 'bar']);
        $figure = new Figure($image, $metaData);

        $this->assertTrue($figure->hasMetaData());
        $this->assertSame($metaData, $figure->getMetaData());
    }

    public function testGetMetaDataSetViaCallback(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $metaData = new MetaData(['foo' => 'bar']);
        $called = 0;

        $metaDataClosure = function (Figure $figure) use (&$called, $metaData): MetaData {
            $this->assertInstanceOf(Figure::class, $figure);
            ++$called;

            return $metaData;
        };

        $figure = new Figure($image, $metaDataClosure);

        $this->assertTrue($figure->hasMetaData());
        $this->assertSame($metaData, $figure->getMetaData());

        $figure->getMetaData(); // second call should be cached
        $this->assertSame(1, $called);
    }

    public function testGetMetaDataFailsIfNotSet(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->expectException(\LogicException::class);

        $figure->getMetaData();
    }

    /**
     * @dataProvider provideLinkAttributesAndPreconditions
     */
    public function testGetLinkAttributes(array $argumentsAndPreconditions, array $expectedAttributes, ?string $expectedHref): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);

        [$attributes, $metaData, $lightBox] = $argumentsAndPreconditions;

        $figure = new Figure($image, $metaData, $attributes, $lightBox);

        $this->assertSame($expectedAttributes, $figure->getLinkAttributes());
        $this->assertSame($expectedHref ?? '', $figure->getLinkHref());
        $this->assertSame($expectedHref, $figure->getLinkAttributes(true)['href'] ?? null);
    }

    public function provideLinkAttributesAndPreconditions(): \Generator
    {
        /** @var LightBoxResult&MockObject $lightBox */
        $lightBox = $this->createMock(LightBoxResult::class);
        $lightBox
            ->method('getLinkHref')
            ->willReturn('path/from/lightbox')
        ;

        $lightBox
            ->method('getGroupIdentifier')
            ->willReturn('12345')
        ;

        yield 'empty set of attributes' => [
            [[], null, null], [], '',
        ];

        yield 'custom attributes' => [
            [
                ['foo' => 'a', 'bar' => 'b'], null, null,
            ],
            [
                'foo' => 'a',
                'bar' => 'b',
            ],
            '',
        ];

        yield 'custom attributes including href' => [
            [
                ['foo' => 'a', 'href' => 'foobar'], null, null,
            ],
            [
                'foo' => 'a',
            ],
            'foobar',
        ];

        yield 'custom attributes including external href' => [
            [
                ['foo' => 'a', 'href' => 'https://example.com'], null, null,
            ],
            [
                'foo' => 'a',
                'rel' => 'noreferrer noopener',
            ],
            'https://example.com',
        ];

        yield 'custom attributes and meta data containing link' => [
            [
                ['foo' => 'a'], new MetaData([MetaData::VALUE_URL => 'foobar']), null,
            ],
            [
                'foo' => 'a',
            ],
            'foobar',
        ];

        yield 'custom attributes and meta data containing external link' => [
            [
                ['foo' => 'a'], new MetaData([MetaData::VALUE_URL => 'https://example.com']), null,
            ],
            [
                'foo' => 'a',
                'rel' => 'noreferrer noopener',
            ],
            'https://example.com',
        ];

        yield 'custom href attribute and meta data containing link' => [
            [
                ['href' => 'this-will-win'], new MetaData([MetaData::VALUE_URL => 'will-be-overwritten']), null,
            ],
            [],
            'this-will-win',
        ];

        yield 'custom attributes and light box' => [
            [
                ['foo' => 'a'], null, $lightBox,
            ],
            [
                'foo' => 'a',
                'data-lightbox' => '12345',
            ],
            'path/from/lightbox',
        ];

        yield 'custom attributes, meta data containing link and light box' => [
            [
                ['foo' => 'a'], new MetaData([MetaData::VALUE_URL => 'will-be-ignored']), $lightBox,
            ],
            [
                'foo' => 'a',
                'data-lightbox' => '12345',
            ],
            'path/from/lightbox',
        ];

        yield 'force-removed href attribute and meta data containing external link' => [
            [
                ['href' => null], new MetaData([MetaData::VALUE_URL => 'https://example.com']), null,
            ],
            [],
            null,
        ];

        yield 'custom attributes, force-set data-lightbox attribute and light-box' => [
            [
                ['foo' => 'a', 'data-lightbox' => 'abcde'], new MetaData([MetaData::VALUE_URL => 'https://example.com']), $lightBox,
            ],
            [
                'foo' => 'a',
                'data-lightbox' => 'abcde',
            ],
            'path/from/lightbox',
        ];
    }

    public function testGetOptions(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $options = ['attributes' => ['class' => 'foo'], 'custom' => new \stdClass()];
        $figure = new Figure($image, null, null, null, $options);

        $this->assertSame($options, $figure->getOptions());
    }

    public function testGetOptionsSetViaCallback(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $options = ['attributes' => ['class' => 'foo'], 'custom' => new \stdClass()];
        $called = 0;

        $optionsClosure = function (Figure $figure) use (&$called, $options): array {
            $this->assertInstanceOf(Figure::class, $figure);
            ++$called;

            return $options;
        };

        $figure = new Figure($image, null, null, null, $optionsClosure);

        $this->assertSame($options, $figure->getOptions());

        $figure->getOptions(); // second call should be cached

        $this->assertSame(1, $called);
    }

    public function testGetOptionsReturnsEmptySetIfNotDefined(): void
    {
        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->assertSame([], $figure->getOptions());
    }

    /**
     * @dataProvider provideLegacyTemplateDataScenarios
     */
    public function testGetLegacyTemplateData(array $preconditions, array $buildAttributes, \Closure $assert): void
    {
        [$metaData, $linkAttributes, $lightBox] = $preconditions;
        [$includeFullMetaData, $floatingProperty, $marginProperty] = $buildAttributes;

        System::setContainer($this->getContainerWithContaoConfiguration());

        $figure = new Figure($this->getImageMock(), $metaData, $linkAttributes, $lightBox);
        $data = $figure->getLegacyTemplateData($marginProperty, $floatingProperty, $includeFullMetaData);

        $assert($data);
    }

    public function provideLegacyTemplateDataScenarios(): \Generator
    {
        $imageSrc = Path::canonicalize(__DIR__.'/../../Fixtures/files/public/foo.jpg');

        yield 'basic image data' => [
            [null, null, null],
            [false, null, null],
            function (array $data) use ($imageSrc): void {
                $this->assertSame(['img foo'], $data['picture']['img']);
                $this->assertSame(['sources foo'], $data['picture']['sources']);
                $this->assertSame($imageSrc, $data['src']);
                $this->assertSame('path/to/resource.jpg', $data['singleSRC']);
                $this->assertSame(100, $data['width']);
                $this->assertSame(50, $data['height']);

                $this->assertTrue($data['addImage']);
                $this->assertFalse($data['fullsize']);
            },
        ];

        $simpleMetaData = new MetaData([
            MetaData::VALUE_ALT => 'a',
            MetaData::VALUE_TITLE => 't',
            'foo' => 'bar',
        ]);

        $metaDataWithLink = new MetaData([
            MetaData::VALUE_TITLE => 't',
            MetaData::VALUE_URL => 'foo://meta',
        ]);

        yield 'with meta data' => [
            [$simpleMetaData, null, null],
            [false, null, null],
            function (array $data): void {
                $this->assertSame('a', $data['picture']['alt']);
                $this->assertSame('t', $data['picture']['title']);
                $this->assertArrayNotHasKey('foo', $data);
            },
        ];

        yield 'with full meta data' => [
            [$simpleMetaData, null, null],
            [true, null, null],
            function (array $data): void {
                $this->assertSame('a', $data['alt']);
                $this->assertSame('t', $data['imageTitle']);
                $this->assertSame('bar', $data['foo']);
            },
        ];

        yield 'with meta data containing link' => [
            [$metaDataWithLink, null, null],
            [true, null, null],
            function (array $data): void {
                $this->assertSame('t', $data['linkTitle']);
                $this->assertSame('foo://meta', $data['imageUrl']);
                $this->assertSame('foo://meta', $data['href']);
                $this->assertSame('', $data['attributes']);

                $this->assertArrayNotHasKey('title', $data['picture']);
            },
        ];

        $basicLinkAttributes = [
            'href' => 'foo://bar',
        ];

        $extendedLinkAttributes = [
            'href' => 'foo://bar',
            'target' => '_blank',
            'foo' => 'bar',
        ];

        yield 'with href link attribute' => [
            [null, $basicLinkAttributes, null],
            [false, null, null],
            function (array $data): void {
                $this->assertSame('', $data['linkTitle']);
                $this->assertSame('foo://bar', $data['href']);
                $this->assertSame('', $data['attributes']);

                $this->assertArrayNotHasKey('title', $data['picture']);
            },
        ];

        yield 'with full meta data and href link attribute' => [
            [$metaDataWithLink, $basicLinkAttributes, null],
            [true, null, null],
            function (array $data): void {
                $this->assertSame('foo://meta', $data['imageUrl']);
                $this->assertSame('foo://bar', $data['href']);
            },
        ];

        yield 'with extended link attributes' => [
            [null, $extendedLinkAttributes, null],
            [false, null, null],
            function (array $data): void {
                $this->assertTrue($data['fullsize']);
                $this->assertSame(' target="_blank" foo="bar"', $data['attributes']);
            },
        ];

        /** @var ImageResult&MockObject $lightBoxImage */
        $lightBoxImage = $this->createMock(ImageResult::class);
        $lightBoxImage
            ->method('getImg')
            ->willReturn(['light box img'])
        ;

        $lightBoxImage
            ->method('getSources')
            ->willReturn(['light box sources'])
        ;

        /** @var LightBoxResult&MockObject $lightBox */
        $lightBox = $this->createMock(LightBoxResult::class);
        $lightBox
            ->method('hasImage')
            ->willReturn(true)
        ;

        $lightBox
            ->method('getImage')
            ->willReturn($lightBoxImage)
        ;

        $lightBox
            ->method('getGroupIdentifier')
            ->willReturn('12345')
        ;

        $lightBox
            ->method('getLinkHref')
            ->willReturn('foo://bar')
        ;

        yield 'with light box' => [
            [null, null, $lightBox],
            [false, null, null],
            function (array $data): void {
                $this->assertSame(['light box img'], $data['lightboxPicture']['img']);
                $this->assertSame(['light box sources'], $data['lightboxPicture']['sources']);

                $this->assertSame('foo://bar', $data['href']);
                $this->assertSame(' data-lightbox="12345"', $data['attributes']);

                $this->assertTrue($data['fullsize']);
                $this->assertSame('', $data['linkTitle']);
                $this->assertArrayNotHasKey('title', $data['picture']);
            },
        ];

        yield 'with legacy properties 1' => [
            [null, null, null],
            [false, 'above', ['top' => '1', 'right' => '2', 'bottom' => '3', 'left' => '4', 'unit' => 'em']],
            function (array $data): void {
                $this->assertTrue($data['addBefore']);
                $this->assertSame('margin:1em 2em 3em 4em;', $data['margin']);
            },
        ];

        yield 'with legacy properties 2' => [
            [null, null, null],
            [false, 'above', 'a:5:{s:3:"top";s:1:"1";s:5:"right";s:1:"2";s:6:"bottom";s:1:"3";s:4:"left";s:1:"4";s:4:"unit";s:2:"em";}'],
            function (array $data): void {
                $this->assertTrue($data['addBefore']);
                $this->assertSame('margin:1em 2em 3em 4em;', $data['margin']);
            },
        ];

        yield 'with legacy properties 3' => [
            [null, null, null],
            [false, 'below', null],
            function (array $data): void {
                $this->assertFalse($data['addBefore']);
            },
        ];
    }

    public function testApplyLegacyTemplate(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $template = new FrontendTemplate('ce_image');

        $figure = new Figure($this->getImageMock());
        $figure->applyLegacyTemplateData($template);

        $this->assertSame(['img foo'], $template->getData()['picture']['img']);

        $template = new \stdClass();
        $figure->applyLegacyTemplateData($template);

        $this->assertSame(['img foo'], $template->picture['img']);
    }

    public function testApplyLegacyTemplateDataDoesNotOverwriteHref(): void
    {
        System::setContainer($this->getContainerWithContaoConfiguration());

        $template = new \stdClass();

        $figure = new Figure($this->getImageMock(), null, ['href' => 'foo://bar']);
        $figure->applyLegacyTemplateData($template);

        $this->assertSame('foo://bar', $template->href);

        $template = new \stdClass();
        $template->href = 'do-not-overwrite';

        $figure->applyLegacyTemplateData($template);

        $this->assertSame('do-not-overwrite', $template->href);
        $this->assertSame('foo://bar', $template->imageHref);
    }

    /**
     * @return ImageResult&MockObject
     */
    private function getImageMock()
    {
        $img = ['img foo'];
        $sources = ['sources foo'];
        $filePath = 'path/to/resource.jpg';
        $imageSrc = Path::canonicalize(__DIR__.'/../../Fixtures/files/public/foo.jpg'); // use existing file so that we can read the file info
        $originalWidth = 100;
        $originalHeight = 50;

        /** @var BoxInterface&MockObject $originalSize */
        $originalSize = $this->createMock(BoxInterface::class);
        $originalSize
            ->method('getWidth')
            ->willReturn($originalWidth)
        ;

        $originalSize
            ->method('getHeight')
            ->willReturn($originalHeight)
        ;

        /** @var ImageDimensions&MockObject $originalDimensions */
        $originalDimensions = $this->createMock(ImageDimensions::class);
        $originalDimensions
            ->method('getSize')
            ->willReturn($originalSize)
        ;

        /** @var ImageResult&MockObject $image */
        $image = $this->createMock(ImageResult::class);
        $image
            ->method('getOriginalDimensions')
            ->willReturn($originalDimensions)
        ;

        $image
            ->method('getImg')
            ->willReturn($img)
        ;

        $image
            ->method('getSources')
            ->willReturn($sources)
        ;

        $image
            ->method('getFilePath')
            ->willReturn($filePath)
        ;

        $image
            ->method('getImageSrc')
            ->willReturn($imageSrc)
        ;

        return $image;
    }
}

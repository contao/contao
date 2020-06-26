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
use PHPUnit\Framework\MockObject\MockObject;

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

//    public function testGetLegacyTemplateData(): void
//    {
//          // todo
//    }
//
//    public function testApplyLegacyTemplateData(): void
//    {
//          // todo
//    }
}

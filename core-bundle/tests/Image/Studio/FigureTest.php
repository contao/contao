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

use Contao\Config;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\LightboxResult;
use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Contao\Files;
use Contao\FrontendTemplate;
use Contao\Image\ImageDimensions;
use Contao\Image\ResizerInterface;
use Contao\System;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImagineInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class FigureTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([System::class, File::class, Files::class, Config::class]);

        parent::tearDown();
    }

    public function testGetImage(): void
    {
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->assertSame($image, $figure->getImage());
    }

    public function testHasNoLightboxOrMetadataByDefault(): void
    {
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->assertFalse($figure->hasLightbox());
        $this->assertFalse($figure->hasMetadata());
    }

    public function testGetLightbox(): void
    {
        $image = $this->createMock(ImageResult::class);
        $lightbox = $this->createMock(LightboxResult::class);
        $figure = new Figure($image, null, null, $lightbox);

        $this->assertTrue($figure->hasLightbox());
        $this->assertSame($lightbox, $figure->getLightbox());
    }

    public function testGetLightboxSetViaCallback(): void
    {
        $image = $this->createMock(ImageResult::class);
        $lightbox = $this->createMock(LightboxResult::class);
        $called = 0;

        $lightboxClosure = function (Figure $figure) use (&$called, $lightbox): LightboxResult {
            $this->assertInstanceOf(Figure::class, $figure);
            ++$called;

            return $lightbox;
        };

        $figure = new Figure($image, null, null, $lightboxClosure);

        $this->assertTrue($figure->hasLightbox());
        $this->assertSame($lightbox, $figure->getLightbox());

        $figure->getLightbox(); // second call should be cached
        $this->assertSame(1, $called);
    }

    public function testGetLightboxReturnsNullIfNotSet(): void
    {
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->assertNull($figure->getLightbox());
    }

    public function testGetMetadata(): void
    {
        $image = $this->createMock(ImageResult::class);
        $metadata = new Metadata(['foo' => 'bar']);
        $figure = new Figure($image, $metadata);

        $this->assertTrue($figure->hasMetadata());
        $this->assertSame($metadata, $figure->getMetadata());
    }

    public function testGetMetadataSetViaCallback(): void
    {
        $image = $this->createMock(ImageResult::class);
        $metadata = new Metadata(['foo' => 'bar']);
        $called = 0;

        $metadataClosure = function (Figure $figure) use (&$called, $metadata): Metadata {
            $this->assertInstanceOf(Figure::class, $figure);
            ++$called;

            return $metadata;
        };

        $figure = new Figure($image, $metadataClosure);

        $this->assertTrue($figure->hasMetadata());
        $this->assertSame($metadata, $figure->getMetadata());

        $figure->getMetadata(); // second call should be cached
        $this->assertSame(1, $called);
    }

    public function testGetMetadataReturnsNullIfNotSet(): void
    {
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->assertNull($figure->getMetadata());
    }

    /**
     * @dataProvider provideLinkAttributesAndPreconditions
     */
    public function testGetLinkAttributes(array $argumentsAndPreconditions, array $expectedAttributes, string|null $expectedHref): void
    {
        $image = $this->createMock(ImageResult::class);

        [$attributes, $metadata, $lightbox] = $argumentsAndPreconditions;

        $figure = new Figure($image, $metadata, $attributes, $lightbox);

        $this->assertSame($expectedAttributes, iterator_to_array($figure->getLinkAttributes()));
        $this->assertSame($expectedHref ?? '', $figure->getLinkHref());
        $this->assertSame($expectedHref, $figure->getLinkAttributes(true)['href'] ?? null);
    }

    public function provideLinkAttributesAndPreconditions(): \Generator
    {
        $lightbox = $this->createMock(LightboxResult::class);
        $lightbox
            ->method('getLinkHref')
            ->willReturn('path/from/lightbox')
        ;

        $lightbox
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

        yield 'custom attributes and metadata containing link' => [
            [
                ['foo' => 'a'], new Metadata([Metadata::VALUE_URL => 'foobar']), null,
            ],
            [
                'foo' => 'a',
            ],
            'foobar',
        ];

        yield 'custom attributes and metadata containing external link' => [
            [
                ['foo' => 'a'], new Metadata([Metadata::VALUE_URL => 'https://example.com']), null,
            ],
            [
                'foo' => 'a',
                'rel' => 'noreferrer noopener',
            ],
            'https://example.com',
        ];

        yield 'custom href attribute and metadata containing link' => [
            [
                ['href' => 'this-will-win'], new Metadata([Metadata::VALUE_URL => 'will-be-overwritten']), null,
            ],
            [],
            'this-will-win',
        ];

        yield 'custom attributes and lightbox' => [
            [
                ['foo' => 'a'], null, $lightbox,
            ],
            [
                'foo' => 'a',
                'data-lightbox' => '12345',
            ],
            'path/from/lightbox',
        ];

        yield 'custom attributes, metadata containing link and lightbox' => [
            [
                ['foo' => 'a'], new Metadata([Metadata::VALUE_URL => 'will-be-ignored']), $lightbox,
            ],
            [
                'foo' => 'a',
                'data-lightbox' => '12345',
            ],
            'path/from/lightbox',
        ];

        yield 'force-removed href attribute and metadata containing external link' => [
            [
                ['href' => null], new Metadata([Metadata::VALUE_URL => 'https://example.com']), null,
            ],
            [],
            null,
        ];

        yield 'custom attributes, force-set data-lightbox attribute and light-box' => [
            [
                ['foo' => 'a', 'data-lightbox' => 'abcde'], new Metadata([Metadata::VALUE_URL => 'https://example.com']), $lightbox,
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
        $image = $this->createMock(ImageResult::class);
        $options = ['attributes' => ['class' => 'foo'], 'custom' => new \stdClass()];
        $figure = new Figure($image, null, null, null, $options);

        $this->assertSame($options, $figure->getOptions());
    }

    public function testGetOptionsSetViaCallback(): void
    {
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
        $image = $this->createMock(ImageResult::class);
        $figure = new Figure($image);

        $this->assertSame([], $figure->getOptions());
    }

    /**
     * @dataProvider provideLegacyTemplateDataScenarios
     */
    public function testGetLegacyTemplateData(array $preconditions, array $buildAttributes, \Closure $assert): void
    {
        [$metadata, $linkAttributes, $lightbox, $options] = $preconditions;
        [$includeFullMetadata, $floatingProperty, $marginProperty] = $buildAttributes;

        $imageFactory = new ImageFactory(
            $this->createMock(ResizerInterface::class),
            $this->createMock(ImagineInterface::class),
            $this->createMock(ImagineInterface::class),
            new Filesystem(),
            $this->createMock(ContaoFramework::class),
            false,
            ['jpeg_quality' => 80],
            ['jpg', 'svg'],
            $this->getFixturesDir()
        );

        $container = $this->getContainerWithContaoConfiguration(Path::canonicalize(__DIR__.'/../../Fixtures'));
        $container->set('contao.image.factory', $imageFactory);

        System::setContainer($container);

        $figure = new Figure($this->mockImage(), $metadata, $linkAttributes, $lightbox, $options);
        $data = $figure->getLegacyTemplateData($marginProperty, $floatingProperty, $includeFullMetadata);

        $assert($data);
    }

    public function provideLegacyTemplateDataScenarios(): \Generator
    {
        yield 'basic image data' => [
            [null, null, null, null],
            [false, null, null],
            function (array $data): void {
                $this->assertSame(['img foo'], $data['picture']['img']);
                $this->assertSame(['sources foo'], $data['picture']['sources']);
                $this->assertSame('https://assets.url/files/public/foo.jpg', $data['src']);
                $this->assertSame('path/to/resource.jpg', $data['singleSRC']);
                $this->assertSame(100, $data['width']);
                $this->assertSame(50, $data['height']);

                $this->assertTrue($data['addImage']);
                $this->assertFalse($data['fullsize']);
            },
        ];

        $simpleMetadata = new Metadata([
            Metadata::VALUE_ALT => 'a',
            Metadata::VALUE_TITLE => 't',
            'foo' => 'bar',
        ]);

        $metadataWithLink = new Metadata([
            Metadata::VALUE_TITLE => 't',
            Metadata::VALUE_URL => 'foo://meta',
        ]);

        $metadataWithHtml = new Metadata([
            Metadata::VALUE_ALT => 'Here <b>is</b> some <i>HTML</i>!',
            Metadata::VALUE_CAPTION => 'Here <b>is</b> some <i>HTML</i>!',
        ]);

        yield 'with metadata' => [
            [$simpleMetadata, null, null, null],
            [false, null, null],
            function (array $data): void {
                $this->assertSame('a', $data['picture']['alt']);
                $this->assertSame('t', $data['picture']['title']);
                $this->assertArrayNotHasKey('foo', $data);
            },
        ];

        yield 'with full metadata' => [
            [$simpleMetadata, null, null, null],
            [true, null, null],
            function (array $data): void {
                $this->assertSame('a', $data['alt']);
                $this->assertSame('t', $data['imageTitle']);
                $this->assertSame('bar', $data['foo']);
            },
        ];

        yield 'with metadata containing link' => [
            [$metadataWithLink, null, null, null],
            [true, null, null],
            function (array $data): void {
                $this->assertSame('t', $data['linkTitle']);
                $this->assertSame('foo://meta', $data['imageUrl']);
                $this->assertSame('foo://meta', $data['href']);
                $this->assertSame('', $data['attributes']);

                $this->assertArrayNotHasKey('title', $data['picture']);
            },
        ];

        yield 'with link title attribute' => [
            [$metadataWithLink, ['title' => 'foo', 'bar' => 'baz'], null, null],
            [true, null, null],
            function (array $data): void {
                $this->assertSame('foo', $data['linkTitle']);
                $this->assertSame(' bar="baz"', $data['attributes'], 'must not contain link attribute');
            },
        ];

        yield 'with metadata containing HTML' => [
            [$metadataWithHtml, null, null, null],
            [true, null, null],
            function (array $data): void {
                $this->assertSame('Here <b>is</b> some <i>HTML</i>!', $data['caption']);
                $this->assertSame('Here &lt;b&gt;is&lt;/b&gt; some &lt;i&gt;HTML&lt;/i&gt;!', $data['alt']);
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
            [null, $basicLinkAttributes, null, null],
            [false, null, null],
            function (array $data): void {
                $this->assertSame('', $data['linkTitle']);
                $this->assertSame('foo://bar', $data['href']);
                $this->assertSame('', $data['attributes']);

                $this->assertArrayNotHasKey('title', $data['picture']);
            },
        ];

        yield 'with full metadata and href link attribute' => [
            [$metadataWithLink, $basicLinkAttributes, null, null],
            [true, null, null],
            function (array $data): void {
                $this->assertSame('foo://meta', $data['imageUrl']);
                $this->assertSame('foo://bar', $data['href']);
            },
        ];

        yield 'with extended link attributes' => [
            [null, $extendedLinkAttributes, null, null],
            [false, null, null],
            function (array $data): void {
                $this->assertTrue($data['fullsize']);
                $this->assertSame(' target="_blank" foo="bar"', $data['attributes']);
            },
        ];

        $lightboxImage = $this->createMock(ImageResult::class);
        $lightboxImage
            ->method('getImg')
            ->willReturn(['lightbox img'])
        ;

        $lightboxImage
            ->method('getSources')
            ->willReturn(['lightbox sources'])
        ;

        $lightbox = $this->createMock(LightboxResult::class);
        $lightbox
            ->method('hasImage')
            ->willReturn(true)
        ;

        $lightbox
            ->method('getImage')
            ->willReturn($lightboxImage)
        ;

        $lightbox
            ->method('getGroupIdentifier')
            ->willReturn('12345')
        ;

        $lightbox
            ->method('getLinkHref')
            ->willReturn('foo://bar')
        ;

        yield 'with lightbox' => [
            [null, null, $lightbox, null],
            [false, null, null],
            function (array $data): void {
                $this->assertSame(['lightbox img'], $data['lightboxPicture']['img']);
                $this->assertSame(['lightbox sources'], $data['lightboxPicture']['sources']);

                $this->assertSame('foo://bar', $data['href']);
                $this->assertSame(' data-lightbox="12345"', $data['attributes']);

                $this->assertTrue($data['fullsize']);
                $this->assertSame('', $data['linkTitle']);
                $this->assertArrayNotHasKey('title', $data['picture']);
            },
        ];

        yield 'with legacy properties 1' => [
            [null, null, null, null],
            [false, 'above', ['top' => '1', 'right' => '2', 'bottom' => '3', 'left' => '4', 'unit' => 'em']],
            function (array $data): void {
                $this->assertTrue($data['addBefore']);
                $this->assertArrayNotHasKey('margin', $data);
            },
        ];

        yield 'with legacy properties 2' => [
            [null, null, null, null],
            [false, 'above', 'a:5:{s:3:"top";s:1:"1";s:5:"right";s:1:"2";s:6:"bottom";s:1:"3";s:4:"left";s:1:"4";s:4:"unit";s:2:"em";}'],
            function (array $data): void {
                $this->assertTrue($data['addBefore']);
                $this->assertArrayNotHasKey('margin', $data);
            },
        ];

        yield 'with legacy properties 3' => [
            [null, null, null, null],
            [false, 'below', null],
            function (array $data): void {
                $this->assertFalse($data['addBefore']);
            },
        ];

        yield 'with template options' => [
            [null, null, null, ['foo' => 'bar', 'addImage' => false]],
            [false, null, null],
            function (array $data): void {
                $this->assertSame('bar', $data['foo']);
                $this->assertFalse($data['addImage']);
            },
        ];
    }

    public function testApplyLegacyTemplate(): void
    {
        $imageFactory = new ImageFactory(
            $this->createMock(ResizerInterface::class),
            $this->createMock(ImagineInterface::class),
            $this->createMock(ImagineInterface::class),
            new Filesystem(),
            $this->createMock(ContaoFramework::class),
            false,
            ['jpeg_quality' => 80],
            ['jpg', 'svg'],
            $this->getFixturesDir()
        );

        $container = $this->getContainerWithContaoConfiguration(Path::canonicalize(__DIR__.'/../../Fixtures'));
        $container->set('contao.image.factory', $imageFactory);

        System::setContainer($container);

        $template = new FrontendTemplate('ce_image');

        $figure = new Figure($this->mockImage());
        $figure->applyLegacyTemplateData($template);

        $this->assertSame(['img foo'], $template->getData()['picture']['img']);
        $this->assertSame($figure, $template->getData()['figure']);

        $template = new \stdClass();
        $figure->applyLegacyTemplateData($template);

        $this->assertSame(['img foo'], $template->picture['img']);
        $this->assertSame($figure, $template->figure);
    }

    public function testApplyLegacyTemplateDataDoesNotOverwriteHref(): void
    {
        $imageFactory = new ImageFactory(
            $this->createMock(ResizerInterface::class),
            $this->createMock(ImagineInterface::class),
            $this->createMock(ImagineInterface::class),
            new Filesystem(),
            $this->createMock(ContaoFramework::class),
            false,
            ['jpeg_quality' => 80],
            ['jpg', 'svg'],
            $this->getFixturesDir()
        );

        $container = $this->getContainerWithContaoConfiguration(Path::canonicalize(__DIR__.'/../../Fixtures'));
        $container->set('contao.image.factory', $imageFactory);

        System::setContainer($container);

        $template = new \stdClass();

        $figure = new Figure($this->mockImage(), null, ['href' => 'foo://bar']);
        $figure->applyLegacyTemplateData($template);

        $this->assertSame('foo://bar', $template->href);

        $template = new \stdClass();
        $template->href = 'do-not-overwrite';

        $figure->applyLegacyTemplateData($template);

        $this->assertSame('do-not-overwrite', $template->href);
        $this->assertSame('foo://bar', $template->imageHref);
    }

    public function testGettingSchemaOrgData(): void
    {
        $figure = new Figure($this->mockImage());

        $this->assertSame(
            [
                '@type' => 'ImageObject',
                'contentUrl' => 'https://assets.url/files/public/foo.jpg',
                'identifier' => 'https://assets.url/files/public/foo.jpg',
            ],
            $figure->getSchemaOrgData()
        );

        $figure = new Figure(
            $this->mockImage(),
            new Metadata([
                Metadata::VALUE_UUID => 'uuid',
                Metadata::VALUE_CAPTION => 'caption',
            ])
        );

        $this->assertSame(
            [
                '@type' => 'ImageObject',
                'caption' => 'caption',
                'contentUrl' => 'https://assets.url/files/public/foo.jpg',
                'identifier' => '#/schema/image/uuid',
            ],
            $figure->getSchemaOrgData()
        );
    }

    /**
     * @return ImageResult&MockObject
     */
    private function mockImage(): ImageResult
    {
        $img = ['img foo'];
        $sources = ['sources foo'];
        $filePath = 'path/to/resource.jpg';
        $imageSrc = 'files/public/foo.jpg'; // use existing file so that we can read the file info
        $originalWidth = 100;
        $originalHeight = 50;

        $originalSize = $this->createMock(BoxInterface::class);
        $originalSize
            ->method('getWidth')
            ->willReturn($originalWidth)
        ;

        $originalSize
            ->method('getHeight')
            ->willReturn($originalHeight)
        ;

        $originalDimensions = $this->createMock(ImageDimensions::class);
        $originalDimensions
            ->method('getSize')
            ->willReturn($originalSize)
        ;

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
            ->willReturnMap([
                [false, "https://assets.url/$imageSrc"],
                [true, $imageSrc],
            ])
        ;

        return $image;
    }
}

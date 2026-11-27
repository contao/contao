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

use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\LightboxResult;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\Controller\ContentElement\ContentElementTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FigureComponentTemplateTest extends ContentElementTestCase
{
    public function testOutputsAllAttributes(): void
    {
        $image = $this->createStub(ImageResult::class);
        $image
            ->method('getImg')
            ->willReturn(['src' => 'files/image.jpg'])
        ;
        $image
            ->method('getSources')
            ->willReturn([['srcset' => 'files/image.webp']])
        ;

        $figure = new Figure(
            $image,
            new Metadata([Metadata::VALUE_CAPTION => 'My caption']),
            ['href' => 'files/image.jpg'],
            null,
            [
                'attr' => ['data-fromfigure' => 'attr'],
                'picture_attr' => ['data-fromfigure' => 'picture_attr'],
                'source_attr' => ['data-fromfigure' => 'source_attr'],
                'img_attr' => ['data-fromfigure' => 'img_attr'],
                'link_attr' => ['data-fromfigure' => 'link_attr'],
                'caption_attr' => ['data-fromfigure' => 'caption_attr'],
            ],
        );

        $environment = $this->getEnvironment($this->getContaoFilesystemLoader(), $this->getDefaultFramework());
        $template = $environment->createTemplate('{% use "@Contao/component/_figure.html.twig" %}{{ block("figure_component") }}');

        $html = $template->render([
            'figure' => $figure,
            'figure_attributes' => new HtmlAttributes(['data-fromtwig' => 'figure_attributes']),
            'picture_attributes' => new HtmlAttributes(['data-fromtwig' => 'picture_attributes']),
            'source_attributes' => new HtmlAttributes(['data-fromtwig' => 'source_attributes']),
            'img_attributes' => new HtmlAttributes(['data-fromtwig' => 'img_attributes']),
            'link_attributes' => new HtmlAttributes(['data-fromtwig' => 'link_attributes']),
            'caption_attributes' => new HtmlAttributes(['data-fromtwig' => 'caption_attributes']),
        ]);

        $expected = <<<'HTML'
            <figure data-fromfigure="attr" data-fromtwig="figure_attributes">
                <a href="files/image.jpg" data-fromfigure="link_attr" data-fromtwig="link_attributes">
                    <picture data-fromfigure="picture_attr" data-fromtwig="picture_attributes">
                        <source srcset="files/image.webp" data-fromfigure="source_attr" data-fromtwig="source_attributes">
                        <img src="files/image.jpg" alt data-fromfigure="img_attr" data-fromtwig="img_attributes">
                    </picture>
                </a>
                <figcaption data-fromfigure="caption_attr" data-fromtwig="caption_attributes">My caption</figcaption>
            </figure>
            HTML;

        $this->assertSame($this->normalizeWhiteSpaces($expected), $this->normalizeWhiteSpaces($html));
    }

    #[DataProvider('provideLinkTitles')]
    public function testOutputsTheLinkTitle(array $linkAttributes, bool $withLightbox, string $expectedLink): void
    {
        $image = $this->createStub(ImageResult::class);
        $image
            ->method('getImg')
            ->willReturn(['src' => 'files/image.jpg'])
        ;

        $lightbox = null;

        if ($withLightbox) {
            $lightbox = $this->createStub(LightboxResult::class);
            $lightbox
                ->method('getLinkHref')
                ->willReturn('files/image.jpg')
            ;
            $lightbox
                ->method('getGroupIdentifier')
                ->willReturn('gal1')
            ;
        }

        $figure = new Figure($image, new Metadata([Metadata::VALUE_TITLE => 'metadata title']), $linkAttributes, $lightbox);

        $environment = $this->getEnvironment($this->getContaoFilesystemLoader(), $this->getDefaultFramework());
        $template = $environment->createTemplate('{% use "@Contao/component/_figure.html.twig" %}{{ block("figure_component") }}');

        $html = $this->normalizeWhiteSpaces($template->render(['figure' => $figure]));

        $this->assertMatchesRegularExpression('#<figure> ?<a [^>]*>#', $html);
        $this->assertSame($expectedLink, preg_replace('#^.*?(<a [^>]*>).*$#s', '$1', $html));
    }

    public static function provideLinkTitles(): iterable
    {
        yield 'lightbox uses the metadata title' => [
            [],
            true,
            '<a href="files/image.jpg" data-lightbox="gal1" title="metadata title">',
        ];

        yield 'lightbox keeps an explicit link title' => [
            ['title' => 'my new title'],
            true,
            '<a title="my new title" href="files/image.jpg" data-lightbox="gal1">',
        ];

        yield 'link without lightbox keeps an explicit link title' => [
            ['href' => 'https://example.com', 'title' => 'my new title'],
            false,
            '<a href="https://example.com" title="my new title" rel="noreferrer noopener">',
        ];
    }
}

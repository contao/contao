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
use Contao\CoreBundle\Tests\Controller\ContentElement\ContentElementTestCase;

class FigureComponentTemplateTest extends ContentElementTestCase
{
    /**
     * @dataProvider provideLinkTitles
     */
    public function testOutputsTheLinkTitle(array $linkAttributes, bool $withLightbox, string $expectedLink): void
    {
        $image = $this->createMock(ImageResult::class);
        $image
            ->method('getImg')
            ->willReturn(['src' => 'files/image.jpg'])
        ;

        $lightbox = null;

        if ($withLightbox) {
            $lightbox = $this->createMock(LightboxResult::class);
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

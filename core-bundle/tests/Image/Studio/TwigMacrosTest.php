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

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\Image\Studio\LightboxResult;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Runtime\SchemaOrgRuntime;
use Symfony\Component\Filesystem\Path;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class TwigMacrosTest extends TestCase
{
    /**
     * @dataProvider provideAttributes
     */
    public function testHtmlAttributesMacro(string $attributes, string $expected): void
    {
        $this->assertSame($expected, $this->renderMacro("html_attributes($attributes)"));
    }

    public function provideAttributes(): \Generator
    {
        yield 'no attributes' => [
            '{}',
            '',
        ];

        yield 'no non-null attributes' => [
            "{ 'foo': null }",
            '',
        ];

        yield 'mixed attributes' => [
            "{ 'foo': 'a', 'bar': 'b', 'foobar': null }",
            ' foo="a" bar="b"',
        ];
    }

    /**
     * @dataProvider provideCaptionOptions
     */
    public function testCaptionMacroWithOptions(string $templateOptions, array $figureOptions, string $expected): void
    {
        $figure = new Figure(
            $this->createMock(ImageResult::class),
            new Metadata([Metadata::VALUE_CAPTION => 'my <b>caption</b>']),
            null,
            null,
            $figureOptions
        );

        $html = $this->renderMacro("caption(figure, $templateOptions)", ['figure' => $figure]);

        $this->assertSame($expected, trim($html));
    }

    public function provideCaptionOptions(): \Generator
    {
        yield 'no options' => [
            '{}',
            [],
            '<figcaption>my <b>caption</b></figcaption>',
        ];

        yield 'figure options' => [
            '{}',
            ['caption_attr' => ['data-foo' => 'bar', 'data-foobar' => 'baz']],
            '<figcaption data-foo="bar" data-foobar="baz">my <b>caption</b></figcaption>',
        ];

        yield 'template options' => [
            "{ 'caption_attr': {'data-foo': 'bar', 'data-foobar': 'baz' }}",
            [],
            '<figcaption data-foo="bar" data-foobar="baz">my <b>caption</b></figcaption>',
        ];

        yield 'template options overwriting figure options' => [
            "{ 'caption_attr': {'data-foobar': 'other' }}",
            ['caption_attr' => ['data-foo' => 'bar', 'data-foobar' => 'baz']],
            '<figcaption data-foo="bar" data-foobar="other">my <b>caption</b></figcaption>',
        ];
    }

    /**
     * @dataProvider provideImgData
     */
    public function testImgMacro(array $imageData, Metadata|null $metadata, string $expected): void
    {
        $image = $this->createMock(ImageResult::class);
        $image
            ->method('getImg')
            ->willReturn($imageData)
        ;

        $html = $this->renderMacro('img(figure)', ['figure' => new Figure($image, $metadata)]);

        $this->assertSame($expected, trim($html));
    }

    public function provideImgData(): \Generator
    {
        yield 'minimal' => [
            ['src' => 'foo.png'],
            null,
            '<img src="foo.png" alt>',
        ];

        yield 'minimal with empty metadata' => [
            ['src' => 'foo.png'],
            new Metadata([]),
            '<img src="foo.png" alt>',
        ];

        yield 'with metadata' => [
            ['src' => 'foo.png'],
            new Metadata([
                Metadata::VALUE_ALT => 'my alt',
                Metadata::VALUE_TITLE => 'my title',
            ]),
            '<img src="foo.png" alt="my alt" title="my title">',
        ];

        yield 'incomplete proportions' => [
            [
                'src' => 'foo.png',
                'width' => 400,
            ],
            null,
            '<img src="foo.png" alt>',
        ];

        yield 'complete proportions' => [
            [
                'src' => 'foo.png',
                'width' => 400,
                'height' => 300,
            ],
            null,
            '<img src="foo.png" alt width="400" height="300">',
        ];

        yield 'full set' => [
            [
                'src' => 'foo.png',
                'srcset' => 'foo-1.png 300w, foo-2png 500w',
                'sizes' => '(max-width: 500px) 100vw, 50vw',
                'width' => 400,
                'height' => 300,
                'loading' => 'lazy',
                'class' => 'my-class',
            ],
            new Metadata([
                Metadata::VALUE_ALT => 'my alt',
                Metadata::VALUE_TITLE => 'my title',
            ]),
            '<img src="foo.png" alt="my alt" title="my title" srcset="foo-1.png 300w, foo-2png 500w" sizes="(max-width: 500px) 100vw, 50vw" width="400" height="300" loading="lazy" class="my-class">',
        ];
    }

    /**
     * @dataProvider provideImgOptions
     */
    public function testImgMacroWithOptions(string $templateOptions, array $figureOptions, string $expected): void
    {
        $image = $this->createMock(ImageResult::class);
        $image
            ->method('getImg')
            ->willReturn([
                'src' => 'foo.png',
                'class' => 'my-class',
            ])
        ;

        $figure = new Figure(
            $image,
            new Metadata([Metadata::VALUE_ALT => 'my alt']),
            null,
            null,
            $figureOptions
        );

        $html = $this->renderMacro("img(figure, $templateOptions)", ['figure' => $figure]);

        $this->assertSame($expected, trim($html));
    }

    public function provideImgOptions(): \Generator
    {
        yield 'no options' => [
            '{}',
            [],
            '<img src="foo.png" alt="my alt" class="my-class">',
        ];

        yield 'figure options (overwriting attributes)' => [
            '{}',
            ['img_attr' => ['data-foo' => 'bar', 'alt' => 'other alt']],
            '<img src="foo.png" alt="other alt" class="my-class" data-foo="bar">',
        ];

        yield 'template options (overwriting attributes)' => [
            "{ 'img_attr': {'data-foo': 'bar', 'alt': 'other alt' }}",
            [],
            '<img src="foo.png" alt="other alt" class="my-class" data-foo="bar">',
        ];

        yield 'template options overwriting figure options' => [
            "{ 'img_attr': {'data-foobar': 'other' }}",
            ['img_attr' => ['class' => 'other-class', 'data-foobar' => 'baz']],
            '<img src="foo.png" alt="my alt" class="other-class" data-foobar="other">',
        ];
    }

    /**
     * @dataProvider providePictureSources
     */
    public function testPictureMacro(array $sources, string $expected): void
    {
        $image = $this->createMock(ImageResult::class);
        $image
            ->method('getSources')
            ->willReturn($sources)
        ;

        $html = $this->renderMacro('picture(figure)', ['figure' => new Figure($image)]);

        // Do not care about the img tag internals
        $html = preg_replace('#<img.*>#', '<img>', $html);

        // Trim whitespaces in between tags for easier comparison
        $html = preg_replace('#>\s+<#', '><', $html);

        $this->assertSame($expected, trim($html));
    }

    public function providePictureSources(): \Generator
    {
        yield 'no sources' => [
            [],
            '<img>',
        ];

        yield 'single source' => [
            [
                [
                    'srcset' => 'foo-1.png 1x, foo-2png 2x',
                    'media' => '(max-width: 1234px)',
                ],
            ],
            '<picture><source srcset="foo-1.png 1x, foo-2png 2x" media="(max-width: 1234px)"><img></picture>',
        ];

        yield 'multiple sources and attributes' => [
            [
                [
                    'srcset' => 'foo-1.png 300w, foo-2png 500w',
                    'sizes' => '400px',
                    'media' => '(max-width: 1234px)',
                ],
                [
                    'srcset' => 'foo-x.png 2000w',
                    'type' => 'image/png',
                ],
            ],
            '<picture><source srcset="foo-1.png 300w, foo-2png 500w" sizes="400px" media="(max-width: 1234px)"><source srcset="foo-x.png 2000w" type="image/png"><img></picture>',
        ];

        yield 'source with dimensions' => [
            [
                [
                    'srcset' => 'foo.png',
                    'width' => 400,
                    'height' => 300,
                ],
            ],
            '<picture><source srcset="foo.png" width="400" height="300"><img></picture>',
        ];
    }

    /**
     * @dataProvider providePictureOptions
     */
    public function testPictureMacroWithOptions(string $templateOptions, array $figureOptions, string $expected): void
    {
        $image = $this->createMock(ImageResult::class);
        $image
            ->method('getSources')
            ->willReturn([
                [
                    'srcset' => 'foo-1.png 1x, foo-2png 2x',
                    'media' => '(max-width: 1234px)',
                ],
            ])
        ;

        $figure = new Figure(
            $image,
            null,
            null,
            null,
            $figureOptions
        );

        $html = $this->renderMacro("picture(figure, $templateOptions)", ['figure' => $figure]);

        // Do not care about the img tag internals
        $html = preg_replace('#<img.*>#', '<img>', $html);

        // Trim whitespaces in between tags for easier comparison
        $html = preg_replace('#>\s+<#', '><', $html);

        $this->assertSame($expected, trim($html));
    }

    public function providePictureOptions(): \Generator
    {
        yield 'no options' => [
            '{}',
            [],
            '<picture><source srcset="foo-1.png 1x, foo-2png 2x" media="(max-width: 1234px)"><img></picture>',
        ];

        yield 'figure options' => [
            '{}',
            [
                'picture_attr' => ['data-foo' => 'foo'],
                'source_attr' => ['data-bar' => 'bar'],
            ],
            '<picture data-foo="foo"><source srcset="foo-1.png 1x, foo-2png 2x" media="(max-width: 1234px)" data-bar="bar"><img></picture>',
        ];

        yield 'template options' => [
            "{ 'picture_attr': {'data-foo': 'foo'}, 'source_attr': {'data-bar': 'bar'} }",
            [],
            '<picture data-foo="foo"><source srcset="foo-1.png 1x, foo-2png 2x" media="(max-width: 1234px)" data-bar="bar"><img></picture>',
        ];

        yield 'template options overwriting figure options' => [
            "{ 'picture_attr': {'data-foo': 'other foo'}, 'source_attr': {'data-bar': 'other bar'} }",
            [
                'picture_attr' => ['data-foo' => 'foo'],
                'source_attr' => ['data-bar' => 'bar'],
            ],
            '<picture data-foo="other foo"><source srcset="foo-1.png 1x, foo-2png 2x" media="(max-width: 1234px)" data-bar="other bar"><img></picture>',
        ];
    }

    /**
     * @dataProvider provideFigureData
     */
    public function testFigureMacro(Metadata|null $metadata, array $linkAttributes, LightboxResult|null $lightbox, string $expected): void
    {
        $figure = new Figure(
            $this->createMock(ImageResult::class),
            $metadata,
            $linkAttributes,
            $lightbox
        );

        $html = $this->renderMacro('figure(figure)', ['figure' => $figure]);

        // Do not care about the img/picture or figcaption tag internals
        $html = preg_replace('#<img[^<]*>#', '<picture>', $html);
        $html = preg_replace('#<figcaption.*</figcaption>#', '<figcaption>', $html);

        // Trim whitespaces in between tags for easier comparison
        $html = preg_replace('#>\s+<#', '><', $html);

        $this->assertSame($expected, trim($html));
    }

    public function provideFigureData(): \Generator
    {
        yield 'minimal' => [
            null,
            [],
            null,
            '<figure><picture></figure>',
        ];

        yield 'with link' => [
            null,
            [
                'href' => 'foo.html',
                'data-link' => 'bar',
            ],
            null,
            '<figure><a href="foo.html" data-link="bar"><picture></a></figure>',
        ];

        $lightbox = $this->createMock(LightboxResult::class);
        $lightbox
            ->method('getLinkHref')
            ->willReturn('lightbox/resource')
        ;

        $lightbox
            ->method('getGroupIdentifier')
            ->willReturn('gal1')
        ;

        yield 'with lightbox link' => [
            null,
            [],
            $lightbox,
            '<figure><a href="lightbox/resource" data-lightbox="gal1"><picture></a></figure>',
        ];

        yield 'with lightbox link and title' => [
            new Metadata([Metadata::VALUE_TITLE => 'foo title']),
            [],
            $lightbox,
            '<figure><a href="lightbox/resource" title="foo title" data-lightbox="gal1"><picture></a></figure>',
        ];

        yield 'with caption' => [
            new Metadata([Metadata::VALUE_CAPTION => 'foo caption']),
            [],
            null,
            '<figure><picture><figcaption></figure>',
        ];
    }

    /**
     * @dataProvider provideFigureOptions
     */
    public function testFigureMacroWithOptions(string $templateOptions, array $figureOptions, string $expected): void
    {
        $lightbox = $this->createMock(LightboxResult::class);
        $lightbox
            ->method('getLinkHref')
            ->willReturn('lightbox/resource')
        ;

        $lightbox
            ->method('getGroupIdentifier')
            ->willReturn('gal1')
        ;

        $figure = new Figure(
            $this->createMock(ImageResult::class),
            new Metadata([Metadata::VALUE_TITLE => 'foo title']),
            [
                'href' => 'foo.html',
                'data-link' => 'bar',
            ],
            $lightbox,
            $figureOptions
        );

        $html = $this->renderMacro("figure(figure, $templateOptions)", ['figure' => $figure]);

        // Do not care about the img/picture or figcaption tag internals
        $html = preg_replace('#<img.*>#', '<picture>', $html);
        $html = preg_replace('#<figcaption.*</figcaption>#', '<figcaption>', $html);

        // Trim whitespaces in between tags for easier comparison
        $html = preg_replace('#>\s+<#', '><', $html);

        $this->assertSame($expected, trim($html));
    }

    public function provideFigureOptions(): \Generator
    {
        yield 'no options' => [
            '{}',
            [],
            '<figure><a href="foo.html" title="foo title" data-link="bar" data-lightbox="gal1"><picture></a></figure>',
        ];

        yield 'figure options' => [
            '{}',
            [
                'attr' => ['data-foo' => 'foo'],
                'link_attr' => ['data-bar' => 'bar'],
            ],
            '<figure data-foo="foo"><a href="foo.html" title="foo title" data-link="bar" data-lightbox="gal1" data-bar="bar"><picture></a></figure>',
        ];

        yield 'template options' => [
            "{ 'attr': {'data-foo': 'foo'}, 'link_attr': {'data-bar': 'bar'} }",
            [],
            '<figure data-foo="foo"><a href="foo.html" title="foo title" data-link="bar" data-lightbox="gal1" data-bar="bar"><picture></a></figure>',
        ];

        yield 'template options overwriting figure options' => [
            "{ 'attr': {'data-foo': 'other foo'}, 'link_attr': {'data-bar': 'other bar'} }",
            [
                'attr' => ['data-foo' => 'foo'],
                'link_attr' => ['data-bar' => 'bar'],
            ],
            '<figure data-foo="other foo"><a href="foo.html" title="foo title" data-link="bar" data-lightbox="gal1" data-bar="other bar"><picture></a></figure>',
        ];
    }

    /**
     * @dataProvider provideAddSchemaOrgOptions
     */
    public function testDoesAddsSchemaOrgDataIfEnabled(string $call, array $schemaData): void
    {
        $figure = new Figure(
            $this->createMock(ImageResult::class),
            new Metadata([
                Metadata::VALUE_TITLE => 'foo title',
                Metadata::VALUE_UUID => '<uuid>',
            ])
        );

        $responseContext = new ResponseContext();
        $jsonLdManager = new JsonLdManager($responseContext);
        $responseContext->add($jsonLdManager);

        $responseContextAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseContextAccessor
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        $this->renderMacro($call, ['figure' => $figure], $responseContextAccessor);

        $graph = $jsonLdManager->getGraphForSchema(JsonLdManager::SCHEMA_ORG)->toArray();

        $this->assertSame($graph['@graph'], $schemaData);
    }

    public function provideAddSchemaOrgOptions(): \Generator
    {
        yield 'default (enabled)' => [
            'figure(figure)',
            [[
                '@type' => 'ImageObject',
                'name' => 'foo title',
                '@id' => '#/schema/image/<uuid>',
            ]],
        ];

        yield 'explicitly enabled' => [
            'figure(figure, {}, true)',
            [[
                '@type' => 'ImageObject',
                'name' => 'foo title',
                '@id' => '#/schema/image/<uuid>',
            ]],
        ];

        yield 'disabled' => [
            'figure(figure, {}, false)',
            [],
        ];
    }

    private function renderMacro(string $call, array $context = [], ResponseContextAccessor|null $responseContextAccessor = null): string
    {
        $templates = [
            '_macros.html.twig' => file_get_contents(
                Path::canonicalize(__DIR__.'/../../../templates/Image/Studio/_macros.html.twig')
            ),
            'test.html.twig' => "{% import \"_macros.html.twig\" as studio %}{{ studio.$call }}",
        ];

        $environment = new Environment(new ArrayLoader($templates));

        $environment->setExtensions([
            new ContaoExtension(
                $environment,
                $this->createMock(TemplateHierarchyInterface::class),
                $this->createMock(ContaoCsrfTokenManager::class)
            ),
        ]);

        $responseContextAccessor ??= $this->createMock(ResponseContextAccessor::class);

        $environment->addRuntimeLoader(
            new FactoryRuntimeLoader([
                SchemaOrgRuntime::class => static fn () => new SchemaOrgRuntime($responseContextAccessor),
            ])
        );

        return $environment->render('test.html.twig', $context);
    }
}

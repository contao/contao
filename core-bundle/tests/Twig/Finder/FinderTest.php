<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Finder;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\Translator;
use Contao\CoreBundle\Twig\Finder\Finder;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Symfony\Component\Translation\MessageCatalogueInterface;

class FinderTest extends TestCase
{
    public function testFindAll(): void
    {
        $finder = $this->getFinder();

        $expected = [
            'ce_table' => 'html.twig',
            'content_element/text' => 'html.twig',
            'content_element/text/foo' => 'html.twig',
            'content_element/text/bar' => 'html.twig',
            'json/thing' => 'json.twig',
        ];

        $this->assertSame($expected, iterator_to_array($finder));
    }

    public function testFindIdentifier(): void
    {
        $finder = $this->getFinder()->identifier('content_element/text');

        $expected = [
            'content_element/text' => 'html.twig',
        ];

        $this->assertSame($expected, iterator_to_array($finder));
    }

    public function testFindExtension(): void
    {
        $finder = $this->getFinder()->extension('json.twig');

        $expected = [
            'json/thing' => 'json.twig',
        ];

        $this->assertSame($expected, iterator_to_array($finder));
    }

    public function testFindName(): void
    {
        $finder = $this->getFinder()->name('@Contao/content_element/text.html.twig');

        $expected = [
            'content_element/text' => 'html.twig',
        ];

        $this->assertSame($expected, iterator_to_array($finder));
    }

    public function testFindWithVariants(): void
    {
        $finder = $this->getFinder()
            ->name('@Contao/content_element/text.html.twig')
            ->withVariants()
        ;

        $expected = [
            'content_element/text' => 'html.twig',
            'content_element/text/foo' => 'html.twig',
            'content_element/text/bar' => 'html.twig',
        ];

        $this->assertSame($expected, iterator_to_array($finder));
    }

    public function testFindWithVariantsOnly(): void
    {
        $finder = $this->getFinder()
            ->identifier('content_element/text')
            ->withVariants(true)
        ;

        $expected = [
            'content_element/text/foo' => 'html.twig',
            'content_element/text/bar' => 'html.twig',
        ];

        $this->assertSame($expected, iterator_to_array($finder));
    }

    public function testFindWithTheme(): void
    {
        $finder = $this->getFinder()
            ->identifier('content_element/text')
            ->withVariants()
            ->withTheme('my_theme')
        ;

        $expected = [
            'content_element/text' => 'html.twig',
            'content_element/text/foo' => 'html.twig',
            'content_element/text/bar' => 'html.twig',
            'content_element/text/baz' => 'html.twig',
        ];

        $this->assertSame($expected, iterator_to_array($finder));
    }

    public function testFindWithWildcard(): void
    {
        $finder = $this->getFinder()
            ->identifier('content_*/text')
            ->enableWildcardSupport()
        ;

        $expected = [
            'content_element/text' => 'html.twig',
        ];

        $this->assertSame($expected, iterator_to_array($finder));
    }

    public function testCount(): void
    {
        $this->assertCount(5, $this->getFinder());
    }

    public function testGetAsTemplateOptions(): void
    {
        $options = $this->getFinder()
            ->identifier('content_element/text')
            ->withVariants()
            ->withTheme('my_theme')
            ->asTemplateOptions()
        ;

        $expected = [
            '' => 'content_element/text [Theme my_theme, App, ContaoCore]',
            'content_element/text/bar' => 'content_element/text/bar [App]',
            'content_element/text/baz' => 'content_element/text/baz [Theme my_theme]',
            'content_element/text/foo' => 'content_element/text/foo [App]',
        ];

        $this->assertSame($expected, $options);
    }

    public function testGetAsTemplateOptionsWithCustomTranslations(): void
    {
        $translations = [
            'content_element/text' => 'Text default',
            'content_element/text/foo' => 'Foo variant',
        ];

        $options = $this->getFinder($translations)
            ->identifier('content_element/text')
            ->withVariants()
            ->asTemplateOptions()
        ;

        $expected = [
            '' => 'Text default [content_element/text • App, ContaoCore]',
            'content_element/text/bar' => 'content_element/text/bar [App]',
            'content_element/text/foo' => 'Foo variant [content_element/text/foo • App]',
        ];

        $this->assertSame($expected, $options);
    }

    private function getFinder(array $translations = []): Finder
    {
        $hierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $hierarchy
            ->method('getInheritanceChains')
            ->willReturnCallback(
                static function (string|null $themeSlug): array {
                    $chains = [
                        'ce_html' => [
                            '/templates/ce_html.html5' => '@Contao_ContaoCoreBundle/ce_html.html5',
                        ],
                        'ce_table' => [
                            '/app/templates/ce_table.html.twig' => '@Contao_App/ce_table.html.twig',
                        ],
                        'content_element/text' => [
                            '/app/templates/content_element/text.html.twig' => '@Contao_App/content_element/text.html.twig',
                            '/templates/content_element/text.html.twig' => '@Contao_ContaoCoreBundle/content_element/text.html.twig',
                        ],
                        'content_element/text/foo' => [
                            '/app/templates/content_element/text/foo.html.twig' => '@Contao_App/content_element/text/foo.html.twig',
                        ],
                        'content_element/text/bar' => [
                            '/app/templates/content_element/text/bar.html.twig' => '@Contao_App/content_element/text/bar.html.twig',
                        ],
                        'json/thing' => [
                            '/app/templates/json/thing.json.twig' => '@Contao_SomeJsonBundle/app/templates/json/thing.json.twig',
                        ],
                    ];

                    if ('my_theme' === $themeSlug) {
                        $chains['content_element/text'] = [
                            '/app/templates/my/theme/content_element/text.html.twig' => '@Contao_Theme_my_theme/content_element/text.html.twig',
                            ...$chains['content_element/text'],
                        ];

                        $chains['content_element/text/baz'] = [
                            '/app/templates/my/theme/content_element/text/foo.html.twig' => '@Contao_Theme_my_theme/content_element/text/foo.html.twig',
                        ];
                    }

                    return $chains;
                },
            )
        ;

        $translator = $this->createMock(Translator::class);
        $translator
            ->method('trans')
            ->willReturnCallback(
                function (string $id, array $parameters, string $domain) use ($translations) {
                    if ('templates' === $domain) {
                        return $translations[$id] ?? throw new \LogicException('Undefined templates translation id.');
                    }

                    $this->assertSame('contao_default', $domain);

                    return match ($id) {
                        'MSC.templatesTheme' => sprintf('Theme %s', $parameters[0]),
                        'MSC.global' => 'Global',
                        default => throw new \LogicException('Unsupported translation id.'),
                    };
                },
            )
        ;

        $catalogue = $this->createMock(MessageCatalogueInterface::class);
        $catalogue
            ->method('has')
            ->willReturnCallback(
                function (string $id, string $domain) use ($translations): bool {
                    $this->assertSame('templates', $domain);

                    return \array_key_exists($id, $translations);
                },
            )
        ;

        $translator
            ->method('getCatalogue')
            ->willReturn($catalogue)
        ;

        return (new FinderFactory($hierarchy, new ThemeNamespace(), $translator))->create();
    }
}

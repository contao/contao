<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\ContaoTwigUtil;

class ContaoTwigUtilTest extends TestCase
{
    /**
     * @dataProvider provideContaoNames
     */
    public function testParseContaoNameSplitsNames(string $name, string $expectedNamespace, ?string $expectedShortName): void
    {
        [$namespace, $shortName] = ContaoTwigUtil::parseContaoName($name);

        $this->assertSame($expectedNamespace, $namespace, 'namespace');
        $this->assertSame($expectedShortName, $shortName, 'short name');
    }

    public function provideContaoNames(): \Generator
    {
        yield 'base namespace' => [
            '@Contao/foo.html.twig',
            'Contao',
            'foo.html.twig',
        ];

        yield 'sub namespace' => [
            '@Contao_Bar/foo.html.twig',
            'Contao_Bar',
            'foo.html.twig',
        ];

        yield 'complex name and namespace' => [
            '@Contao_a-b_c/f~oo.html.twig',
            'Contao_a-b_c',
            'f~oo.html.twig',
        ];

        yield 'legacy template' => [
            '@Contao_Foo/foo.html5',
            'Contao_Foo',
            'foo.html5',
        ];

        yield 'only base namespace' => [
            '@Contao',
            'Contao',
            null,
        ];

        yield 'only sub namespace' => [
            '@Contao_foo_bar',
            'Contao_foo_bar',
            null,
        ];
    }

    /**
     * @dataProvider provideInvalidNamespaces
     */
    public function testParseContaoNameIgnoresInvalidNamespaces(string $name): void
    {
        $this->assertNull(ContaoTwigUtil::parseContaoName($name));
    }

    public function provideInvalidNamespaces(): \Generator
    {
        yield 'not a Contao namespace' => ['@Foobar/foo.html.twig'];
        yield 'invalid characters' => ['@Contao_:Foo'];
        yield 'no namespace' => ['foo.html.twig'];
        yield 'empty input' => [''];
    }

    /**
     * @dataProvider provideNames
     */
    public function testGetIdentifier(string $name, string $expectedIdentifier): void
    {
        $this->assertSame($expectedIdentifier, ContaoTwigUtil::getIdentifier($name));
    }

    public function provideNames(): \Generator
    {
        yield 'html5 template' => [
            'bar.html5',
            'bar',
        ];

        yield 'HTML Twig template' => [
            'bar.html.twig',
            'bar',
        ];

        yield 'JSON Twig template' => [
            'bar.json.twig',
            'bar',
        ];

        yield 'complex name (html5)' => [
            '@Foo/bar/foo.html5',
            'bar/foo',
        ];

        yield 'complex name (Twig)' => [
            '@Foo/bar/foo.html.twig',
            'bar/foo',
        ];

        yield 'not a Contao template extension' => [
            'foo/bar.txt',
            'foo/bar.txt',
        ];

        yield 'already an identifier' => [
            'foo',
            'foo',
        ];
    }

    /**
     * @dataProvider provideLegacyTemplateNames
     */
    public function testIsLegacyTemplate(string $name, bool $isLegacyTemplate): void
    {
        $this->assertSame($isLegacyTemplate, ContaoTwigUtil::isLegacyTemplate($name));
    }

    public function provideLegacyTemplateNames(): \Generator
    {
        yield 'base namespace' => [
            '@Contao/bar.html5',
            true,
        ];

        yield 'sub namespace' => [
            '@Contao_Foo/bar.html5',
            true,
        ];

        yield 'uppercase extension' => [
            '@Contao_Foo/bar.HTML5',
            true,
        ];

        yield 'invalid file extension' => [
            '@Contao/bar.html.twig',
            false,
        ];

        yield 'not a Contao namespace' => [
            '@Foo/bar.html5',
            false,
        ];

        yield 'not a logical name (just namespace)' => [
            '@Foo',
            false,
        ];

        yield 'not a logical name (just short name)' => [
            'bar.html5',
            false,
        ];

        yield 'not a logical name (just identifier)' => [
            'bar',
            false,
        ];

        yield 'empty input' => [
            '',
            false,
        ];
    }

    /**
     * @dataProvider providePaths
     */
    public function testGetExtension(string $path, string $extension): void
    {
        $this->assertSame($extension, ContaoTwigUtil::getExtension($path));
    }

    public function providePaths(): \Generator
    {
        yield 'with .twig suffix' => ['foo/bar.baz.html.twig', 'html.twig'];

        yield 'without .twig suffix' => ['foo/bar.baz.json', 'json'];

        yield 'no extension' => ['foo/bar', ''];
    }
}

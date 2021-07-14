<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inheritance;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inheritance\DynamicExtendsTokenParser;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;

class DynamicExtendsTokenParserTest extends TestCase
{
    public function testGetTag(): void
    {
        $tokenParser = new DynamicExtendsTokenParser($this->createMock(TemplateHierarchyInterface::class));

        $this->assertSame('extends', $tokenParser->getTag());
    }

    /**
     * @dataProvider provideSources
     */
    public function testHandlesContaoExtends(string $code, string $expectedParent): void
    {
        $templateHierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $templateHierarchy
            ->method('getDynamicParent')
            ->with('foo.html.twig', '/path/to/the/template.html.twig')
            ->willReturn('<parent>')
        ;

        $environment = new Environment($this->createMock(LoaderInterface::class));
        $environment->addTokenParser(new DynamicExtendsTokenParser($templateHierarchy));

        $source = new Source(
            $code,
            'template.html.twig',
            '/path/to/the/template.html.twig'
        );

        $tokenStream = (new Lexer($environment))->tokenize($source);

        $node = (new Parser($environment))->parse($tokenStream);
        $parent = $node->getNode('parent');

        $this->assertSame($expectedParent, $parent->getAttribute('value'));
    }

    public function provideSources(): \Generator
    {
        yield 'regular extend' => [
            "{% extends '@Foo/bar.html.twig' %}",
            '@Foo/bar.html.twig',
        ];

        yield 'Contao extend' => [
            "{% extends '@Contao/foo.html.twig' %}",
            '<parent>',
        ];
    }

    /**
     * @dataProvider provideSourcesWithErrors
     */
    public function testValidatesTokenStream(string $code, string $expectedException): void
    {
        $environment = new Environment($this->createMock(LoaderInterface::class));

        $environment->addTokenParser(new DynamicExtendsTokenParser(
            $this->createMock(TemplateHierarchyInterface::class)
        ));

        $source = new Source(
            $code,
            'template.html.twig',
            '/path/to/the/template.html.twig'
        );

        $tokenStream = (new Lexer($environment))->tokenize($source);
        $parser = new Parser($environment);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage($expectedException);

        $parser->parse($tokenStream);
    }

    public function provideSourcesWithErrors(): \Generator
    {
        yield 'extend from within a block' => [
            "{% block b %}{% extends '@Foo/bar.html.twig' %}{% endblock %}",
            'Cannot use "extends" in a block.',
        ];

        yield 'extend from within macro' => [
            "{% macro m() %}{% extends '@Foo/bar.html.twig' %}{% endmacro %}",
            'Cannot use "extends" in a macro.',
        ];

        yield 'multiple extends' => [
            "{% extends '@Foo/bar1.html.twig' %}{% extends '@Foo/bar2.html.twig' %}",
            'Multiple extends tags are forbidden.',
        ];
    }
}

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
    public function testHandlesContaoExtends(string $code, string ...$expectedStrings): void
    {
        $templateHierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $templateHierarchy
            ->method('getDynamicParent')
            ->willReturnCallback(
                function (string $name, string $path) {
                    $this->assertSame('/path/to/the/template.html.twig', $path);

                    $hierarchy = [
                        'foo.html.twig' => '<foo-parent>',
                        'bar.html.twig' => '<bar-parent>',
                    ];

                    if (null !== ($resolved = $hierarchy[$name] ?? null)) {
                        return $resolved;
                    }

                    throw new \LogicException('Template not found in hierarchy.');
                }
            )
        ;

        $environment = new Environment($this->createMock(LoaderInterface::class));
        $environment->addTokenParser(new DynamicExtendsTokenParser($templateHierarchy));

        $source = new Source(
            $code,
            'template.html.twig',
            '/path/to/the/template.html.twig'
        );

        $tokenStream = (new Lexer($environment))->tokenize($source);
        $serializedParentNode = (new Parser($environment))->parse($tokenStream)->getNode('parent');

        foreach ($expectedStrings as $expectedString) {
            $this->assertStringContainsString($expectedString, (string) $serializedParentNode);
        }
    }

    public function provideSources(): \Generator
    {
        yield 'regular extend' => [
            "{% extends '@Foo/bar.html.twig' %}",
            '@Foo/bar.html.twig',
        ];

        yield 'Contao extend' => [
            "{% extends '@Contao/foo.html.twig' %}",
            '<foo-parent>',
        ];

        yield 'conditional extend' => [
            "{% extends x == 1 ? '@Foo/bar.html.twig' : '@Foo/baz.html.twig' %}",
            '@Foo/bar.html.twig', '@Foo/baz.html.twig',
        ];

        yield 'conditional Contao extend' => [
            "{% extends x == 1 ? '@Contao/foo.html.twig' : '@Contao/bar.html.twig' %}",
            '<foo-parent>', '<bar-parent>',
        ];

        yield 'optional extend' => [
            "{% extends ['a.html.twig', 'b.html.twig'] %}",
            'a.html.twig', 'b.html.twig',
        ];

        yield 'optional Contao extend' => [
            // Files missing in the hierarchy should be ignored in this case
            "{% extends ['@Contao/missing.html.twig', '@Contao/bar.html.twig']  %}",
            '@Contao/missing.html.twig', '<bar-parent>',
        ];
    }

    public function testFailsWhenExtendingAnInvalidTemplate(): void
    {
        $templateHierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $templateHierarchy
            ->method('getDynamicParent')
            ->with('foo')
            ->willThrowException(new \LogicException('Template not found in hierarchy.'))
        ;

        $environment = new Environment($this->createMock(LoaderInterface::class));
        $environment->addTokenParser(new DynamicExtendsTokenParser($templateHierarchy));

        // Use a conditional expression here, so that we can test rethrowing
        // exceptions in case the parent node is not an ArrayExpression
        $source = new Source("{% extends true ? '@Contao/foo' : '' %}", 'template.html.twig');
        $tokenStream = (new Lexer($environment))->tokenize($source);
        $parser = new Parser($environment);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Template not found in hierarchy.');

        $parser->parse($tokenStream);
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

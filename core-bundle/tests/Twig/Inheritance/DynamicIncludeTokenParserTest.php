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
use Contao\CoreBundle\Twig\Inheritance\DynamicIncludeTokenParser;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Twig\Environment;
use Twig\Lexer;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;

class DynamicIncludeTokenParserTest extends TestCase
{
    public function testGetTag(): void
    {
        $tokenParser = new DynamicIncludeTokenParser($this->createMock(TemplateHierarchyInterface::class));

        $this->assertSame('include', $tokenParser->getTag());
    }

    /**
     * @dataProvider provideSources
     */
    public function testHandlesContaoIncludes(string $code, string ...$expectedStrings): void
    {
        $templateHierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $templateHierarchy
            ->method('getFirst')
            ->willReturnCallback(
                static function (string $name) {
                    $hierarchy = [
                        'foo.html.twig' => '<foo-template>',
                        'bar.html.twig' => '<bar-template>',
                    ];

                    if (null !== ($resolved = $hierarchy[$name] ?? null)) {
                        return $resolved;
                    }

                    throw new \LogicException('Template not found in hierarchy.');
                }
            )
        ;

        $environment = new Environment($this->createMock(LoaderInterface::class));
        $environment->addTokenParser(new DynamicIncludeTokenParser($templateHierarchy));

        $source = new Source($code, 'template.html.twig');
        $tokenStream = (new Lexer($environment))->tokenize($source);
        $serializedTree = (new Parser($environment))->parse($tokenStream)->__toString();

        foreach ($expectedStrings as $expectedString) {
            $this->assertStringContainsString($expectedString, $serializedTree);
        }
    }

    public function provideSources(): \Generator
    {
        yield 'regular include' => [
            "{% include '@Foo/bar.html.twig' %}",
            '@Foo/bar.html.twig',
        ];

        yield 'Contao include' => [
            "{% include '@Contao/foo.html.twig' %}",
            '<foo-template>',
        ];

        yield 'conditional includes' => [
            "{% include x == 1 ? '@Contao/foo.html.twig' : '@Foo/bar.html.twig' %}",
            '<foo-template>', '@Foo/bar.html.twig',
        ];

        yield 'conditional Contao includes' => [
            "{% include x == 1 ? '@Contao/foo.html.twig' : '@Contao/bar.html.twig' %}",
            '<foo-template>', '<bar-template>',
        ];

        yield 'optional includes' => [
            "{% include ['a.html.twig', 'b.html.twig'] %}",
            'a.html.twig', 'b.html.twig',
        ];

        yield 'optional Contao includes' => [
            // Files missing in the hierarchy should be ignored in this case
            "{% include ['@Contao/missing.html.twig', '@Contao/bar.html.twig'] %}",
            '@Contao/missing.html.twig', '<bar-template>',
        ];
    }

    public function testEnhancesErrorMessageWhenIncludingAnInvalidTemplate(): void
    {
        $templateHierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $templateHierarchy
            ->method('getFirst')
            ->with('foo')
            ->willThrowException(
                new \LogicException('<original message>')
            )
        ;

        $environment = new Environment($this->createMock(LoaderInterface::class));
        $environment->addTokenParser(new DynamicIncludeTokenParser($templateHierarchy));

        // Use a conditional expression here, so that we can test rethrowing
        // exceptions in case the parent node is not an ArrayExpression
        $source = new Source("{% include true ? '@Contao/foo' : '' %}", 'template.html.twig');
        $tokenStream = (new Lexer($environment))->tokenize($source);
        $parser = new Parser($environment);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('<original message> Did you try to include a non-existent template or a template from a theme directory?');

        $parser->parse($tokenStream);
    }
}

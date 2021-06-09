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
    public function testSetsParent(string $code, string $expectedParent): void
    {
        $templateHierarchy = $this->createMock(TemplateHierarchyInterface::class);
        $templateHierarchy
            ->method('getDynamicParent')
            ->with('foo.html.twig', '/path/to/the/template.html.twig')
            ->willReturn('<dynamic parent>')
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

        yield 'Contao dynamic extend' => [
            "{% extends '@Contao/foo.html.twig' %}",
            '<dynamic parent>',
        ];
    }
}

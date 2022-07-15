<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\ResponseContext;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\ResponseContext\AddTokenParser;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\Source;

class AddTokenParserTest extends TestCase
{
    public function testGetTag(): void
    {
        $tokenParser = new AddTokenParser(ContaoExtension::class);

        $this->assertSame('add', $tokenParser->getTag());
    }

    /**
     * @dataProvider provideSources
     *
     * @param list<string>|array<string, string> $expectedHeadContent
     * @param list<string>|array<string, string> $expectedBodyContent
     */
    public function testAddsContent(string $code, array $expectedHeadContent, array $expectedBodyContent): void
    {
        $environment = new Environment($this->createMock(LoaderInterface::class));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(TemplateHierarchyInterface::class),
                $this->createMock(ContaoCsrfTokenManager::class)
            )
        );

        $environment->addTokenParser(new AddTokenParser(ContaoExtension::class));
        $environment->setLoader(new ArrayLoader(['template.html.twig' => $code]));
        $environment->render('template.html.twig');

        $this->assertSame($GLOBALS['TL_HEAD'] ?? [], $expectedHeadContent);
        $this->assertSame($GLOBALS['TL_BODY'] ?? [], $expectedBodyContent);

        unset($GLOBALS['TL_HEAD'], $GLOBALS['TL_BODY']);
    }

    public function provideSources(): \Generator
    {
        yield 'add to head' => [
            '{% add to head %}head content{% endadd %}',
            ['head content'],
            [],
        ];

        yield 'add to body' => [
            '{% add to body %}body content{% endadd %}',
            [],
            ['body content'],
        ];

        yield 'add multiple' => [
            "{% add to head %}head content{% endadd %}\n".
            "{% add to body %}body content{% endadd %}\n".
            "{% add to head %}head content{% endadd %}\n".
            '{% add to body %}body content{% endadd %}',
            ['head content', 'head content'],
            ['body content', 'body content'],
        ];

        yield 'add named to head' => [
            "{% add 'foo' to head %}head content{% endadd %}\n".
            "{% add 'foo' to head %}overwritten head content{% endadd %}",
            ['foo' => 'overwritten head content'],
            [],
        ];

        yield 'add named to body' => [
            "{% add 'foo' to body %}body content{% endadd %}\n".
            "{% add 'foo' to body %}overwritten body content{% endadd %}",
            [],
            ['foo' => 'overwritten body content'],
        ];

        yield 'add multiple named' => [
            "{% add 'foo' to head %}head content{% endadd %}\n".
            "{% add 'foo' to body %}body content{% endadd %}\n".
            "{% add 'foo' to head %}head content{% endadd %}\n".
            "{% add 'foo' to body %}body content{% endadd %}",
            ['foo' => 'head content'],
            ['foo' => 'body content'],
        ];

        yield 'add with complex content' => [
            "{% set var = 'bar' %}\n".
            '{% add to body %}foo {{ var }}{% endadd %}',
            [],
            ['foo bar'],
        ];
    }

    /**
     * @dataProvider provideInvalidSources
     */
    public function testValidatesSource(string $code, string $expectedException): void
    {
        $environment = new Environment($this->createMock(LoaderInterface::class));
        $environment->addTokenParser(new AddTokenParser(ContaoExtension::class));

        $parser = new Parser($environment);
        $source = new Source($code, 'template.html.twig');
        $tokenStream = (new Lexer($environment))->tokenize($source);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage($expectedException);

        $parser->parse($tokenStream);
    }

    public function provideInvalidSources(): \Generator
    {
        yield 'invalid target' => [
            '{% add to stomach %}apple{% endadd %}',
            'The parameter "stomach" is not a valid location for the "add" tag, use "head" or "body" instead in "template.html.twig"',
        ];

        yield 'malformed target' => [
            '{% add to "head" %}foo{% endadd %}',
            'Unexpected token "string" of value "head" ("name" expected) in "template.html.twig"',
        ];

        yield 'missing target' => [
            '{% add %}foo{% endadd %}',
            'Unexpected token "end of statement block" ("name" expected with value "to") in "template.html.twig"',
        ];

        yield 'parameter at wrong place' => [
            '{% add to body "foo" %}bar{% endadd %}',
            'Unexpected token "string" of value "foo" ("end of statement block" expected) in "template.html.twig"',
        ];
    }
}

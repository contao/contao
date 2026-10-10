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

use Contao\CoreBundle\Routing\ResponseContext\HtmlBodyBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\ResponseContext\AddTokenParser;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Contao\CoreBundle\Twig\Runtime\HtmlDocumentRuntime;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Parser;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use Twig\Source;

class AddTokenParserTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HEAD'], $GLOBALS['TL_STYLE_SHEETS'], $GLOBALS['TL_BODY']);

        parent::tearDown();
    }

    public function testGetTag(): void
    {
        $tokenParser = new AddTokenParser();

        $this->assertSame('add', $tokenParser->getTag());
    }

    /**
     * @param list<string>|array<string, string> $expectedHeadContent
     * @param list<string>|array<string, string> $expectedBodyContent
     */
    #[DataProvider('provideSources')]
    public function testAddsContent(string $code, array $expectedHeadContent, array $expectedStyleSheetContent, array $expectedBodyContent): void
    {
        $environment = new Environment($this->createStub(LoaderInterface::class));
        $responseContext = new ResponseContext()
            ->add($body = new HtmlBodyBag())
            ->add($head = new HtmlHeadBag())
        ;
        $accessor = $this->createStub(ResponseContextAccessor::class);
        $accessor
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;
        $environment->addRuntimeLoader(new FactoryRuntimeLoader([
            HtmlDocumentRuntime::class => static fn () => new HtmlDocumentRuntime($accessor),
        ]));

        $extension = new ContaoExtension(
            $environment,
            $this->createStub(ContaoFilesystemLoader::class),
            $this->createStub(ContaoVariable::class),
            new InspectorNodeVisitor($this->createStub(Storage::class), $environment),
        );

        $environment->addExtension($extension);

        $environment->addTokenParser(new AddTokenParser());
        $environment->setLoader(new ArrayLoader(['template.html.twig' => $code]));
        $this->assertSame('', $environment->render('template.html.twig'));

        $this->assertSame($expectedHeadContent, $this->getAddedHeadContent($head, DocumentLocation::head));
        $this->assertSame($expectedStyleSheetContent, $this->getAddedHeadContent($head, DocumentLocation::stylesheets));
        $this->assertSame($expectedBodyContent, $body->all());

        $this->assertArrayNotHasKey('TL_HEAD', $GLOBALS);
        $this->assertArrayNotHasKey('TL_STYLE_SHEETS', $GLOBALS);
        $this->assertArrayNotHasKey('TL_BODY', $GLOBALS);
    }

    public static function provideSources(): iterable
    {
        yield 'add to head' => [
            '{% add to head %}head content{% endadd %}',
            ['head content'],
            [],
            [],
        ];

        yield 'add to stylesheets' => [
            '{% add to stylesheets %}stylesheets content{% endadd %}',
            [],
            ['stylesheets content'],
            [],
        ];

        yield 'add to body' => [
            '{% add to body %}body content{% endadd %}',
            [],
            [],
            ['body content'],
        ];

        yield 'add multiple' => [
            "{% add to head %}head content{% endadd %}\n".
            "{% add to stylesheets %}stylesheets content{% endadd %}\n".
            "{% add to body %}body content{% endadd %}\n".
            "{% add to head %}head content{% endadd %}\n".
            "{% add to stylesheets %}stylesheets content{% endadd %}\n".
            '{% add to body %}body content{% endadd %}',
            ['head content', 'head content'],
            ['stylesheets content', 'stylesheets content'],
            ['body content', 'body content'],
        ];

        yield 'add named to head' => [
            "{% add 'foo' to head %}head content{% endadd %}\n".
            "{% add 'foo' to head %}overwritten head content{% endadd %}",
            ['foo' => 'overwritten head content'],
            [],
            [],
        ];

        yield 'add named to stylesheets' => [
            "{% add 'foo' to stylesheets %}stylesheets content{% endadd %}\n".
            "{% add 'foo' to stylesheets %}overwritten stylesheets content{% endadd %}",
            [],
            ['foo' => 'overwritten stylesheets content'],
            [],
        ];

        yield 'add named to body' => [
            "{% add 'foo' to body %}body content{% endadd %}\n".
            "{% add 'foo' to body %}overwritten body content{% endadd %}",
            [],
            [],
            ['foo' => 'overwritten body content'],
        ];

        yield 'add multiple named' => [
            "{% add 'foo' to head %}head content{% endadd %}\n".
            "{% add 'foo' to stylesheets %}stylesheets content{% endadd %}\n".
            "{% add 'foo' to body %}body content{% endadd %}\n".
            "{% add 'foo' to head %}head content{% endadd %}\n".
            "{% add 'foo' to stylesheets %}stylesheets content{% endadd %}\n".
            "{% add 'foo' to body %}body content{% endadd %}",
            ['foo' => 'head content'],
            ['foo' => 'stylesheets content'],
            ['foo' => 'body content'],
        ];

        yield 'add with complex content' => [
            "{% set var = 'bar' %}\n".
            '{% add to body %}foo {{ var }}{% endadd %}',
            [],
            [],
            ['foo bar'],
        ];

        yield 'add after to head' => [
            "{% add 'second' to head after 'first' %}second{% endadd %}\n".
            "{% add 'first' to head %}first{% endadd %}",
            ['first' => 'first', 'second' => 'second'],
            [],
            [],
        ];

        yield 'add before to stylesheets' => [
            "{% add 'second' to stylesheets %}second{% endadd %}\n".
            "{% add 'first' to stylesheets before 'second' %}first{% endadd %}",
            [],
            ['first' => 'first', 'second' => 'second'],
            [],
        ];

        yield 'add after to body' => [
            "{% add 'second' to body after 'first' %}second{% endadd %}\n".
            "{% add 'first' to body %}first{% endadd %}",
            [],
            [],
            ['first' => 'first', 'second' => 'second'],
        ];
    }

    public function testFallsBackToLegacyGlobalsWithoutAResponseContext(): void
    {
        $environment = new Environment(new ArrayLoader([
            'template.html.twig' => '{% add "legacy" to head %}<meta data-legacy>{% endadd %}',
        ]));
        $environment->addRuntimeLoader(new FactoryRuntimeLoader([
            HtmlDocumentRuntime::class => fn () => new HtmlDocumentRuntime($this->createStub(ResponseContextAccessor::class)),
        ]));
        $environment->addTokenParser(new AddTokenParser());

        $this->expectUserDeprecationMessageMatches('/Using the Twig "add" tag without the corresponding response context bag is deprecated/');

        $this->assertSame('', $environment->render('template.html.twig'));
        $this->assertSame(['legacy' => '<meta data-legacy>'], $GLOBALS['TL_HEAD']);
    }

    #[DataProvider('provideInvalidSources')]
    public function testValidatesSource(string $code, string $expectedException): void
    {
        $environment = new Environment($this->createStub(LoaderInterface::class));
        $environment->addTokenParser(new AddTokenParser());

        $parser = new Parser($environment);
        $source = new Source($code, 'template.html.twig');
        $tokenStream = new Lexer($environment)->tokenize($source);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage($expectedException);

        $parser->parse($tokenStream);
    }

    public static function provideInvalidSources(): iterable
    {
        yield 'invalid target' => [
            '{% add to stomach %}apple{% endadd %}',
            'The parameter "stomach" is not a valid location for the "add" tag, use "head" or "stylesheets" or "body" instead in "template.html.twig"',
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

        yield 'ordering without an identifier' => [
            "{% add to body after 'foo' %}bar{% endadd %}",
            'An identifier is required when ordering content with the "add" tag in "template.html.twig"',
        ];

        yield 'ordering without a reference' => [
            "{% add 'foo' to body after %}bar{% endadd %}",
            'Unexpected token "end of statement block" ("string" expected) in "template.html.twig"',
        ];
    }

    /**
     * @return array<array-key, string>
     */
    private function getAddedHeadContent(HtmlHeadBag $head, DocumentLocation $location): array
    {
        $prefix = "contao.twig.{$location->value}.";
        $content = [];
        $hasNamedContent = false;

        foreach ($head->all() as $identifier => $tag) {
            if (\is_string($tag) && str_starts_with($identifier, $prefix)) {
                $key = substr($identifier, \strlen($prefix));
                $hasNamedContent = $hasNamedContent || !ctype_digit($key);
                $content[$key] = $tag;
            }
        }

        return $hasNamedContent ? $content : array_values($content);
    }
}

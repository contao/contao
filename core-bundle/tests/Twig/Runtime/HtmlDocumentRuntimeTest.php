<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\HtmlBodyBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Contao\CoreBundle\Twig\Runtime\HtmlDocumentRuntime;
use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlDocumentRuntimeTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HEAD'], $GLOBALS['TL_STYLE_SHEETS'], $GLOBALS['TL_BODY']);
    }

    public function testRendersStructuredAndRawHeadTags(): void
    {
        $runtime = new HtmlDocumentRuntime($this->createStub(ResponseContextAccessor::class));

        $this->assertSame(
            '<meta name="theme-color" content="&quot;dark&quot;">',
            $runtime->renderHtmlTag(HtmlTag::meta(['name' => 'theme-color', 'content' => '"dark"'])),
        );
        $this->assertSame('<title>&lt;Title&#039;s&gt;</title>', $runtime->renderHtmlTag(HtmlTag::title("<Title's>")));
        $this->assertSame('<script src="/app.js" defer></script>', $runtime->renderHtmlTag(HtmlTag::script('/app.js', ['defer' => true]), 'app.script'));
        $this->assertSame('<meta data-legacy>', $runtime->renderHtmlTag('<meta data-legacy>'));
    }

    public function testAddsIdentifiersAsDataAttributesInDebugMode(): void
    {
        $runtime = new HtmlDocumentRuntime($this->createStub(ResponseContextAccessor::class), true);

        $this->assertSame(
            '<script src="/app.js" data-contao-tag="script[src=&quot;/app.js&quot;]"></script>',
            $runtime->renderHtmlTag(HtmlTag::script('/app.js'), 'script[src="/app.js"]'),
        );
        $this->assertSame(
            '<!-- contao-tag: raw --><script data-raw></script>',
            $runtime->renderHtmlTag('<script data-raw></script>', 'raw'),
        );
        $this->assertSame(
            '<!-- contao-tag: theme --><link rel="stylesheet">',
            $runtime->renderHtmlTag('<link rel="stylesheet">', 'contao.twig.stylesheets.theme'),
        );
        $this->assertSame(
            '<!-- contao-tag: replacement --><script data-contao-tag="existing"></script>',
            $runtime->renderHtmlTag('<script data-contao-tag="existing"></script>', 'replacement'),
        );
        $this->assertSame(
            '<!-- contao-tag: price- -break --><meta>',
            $runtime->renderHtmlTag('<meta>', "price--break\n"),
        );
    }

    public function testAddsCspNoncesToInlineScriptsAndStyles(): void
    {
        $directives = new DirectiveSet(new PolicyManager());
        $directives->setDirective('script-src', "'self'");
        $directives->setDirective('style-src', "'self'");

        $responseContext = new ResponseContext()->add(new CspHandler($directives));
        $runtime = new HtmlDocumentRuntime($this->createAccessor($responseContext));
        $script = HtmlTag::inlineScript('alert(1)');

        $this->assertMatchesRegularExpression(
            '/^<script nonce="[^"]+">alert\(1\)<\/script>$/',
            $runtime->renderHtmlTag($script),
        );
        $this->assertFalse(isset($script->getAttributes()['nonce']));
        $this->assertMatchesRegularExpression(
            '/^<style nonce="[^"]+">body \{\}<\/style>$/',
            $runtime->renderHtmlTag(HtmlTag::inlineStyle('body {}')),
        );
        $this->assertSame(
            '<script nonce="manual">alert(1)</script>',
            $runtime->renderHtmlTag($script->withAttribute('nonce', 'manual')),
        );
    }

    public function testAddsTwigContentToTheResponseContext(): void
    {
        $responseContext = new ResponseContext()
            ->add($body = new HtmlBodyBag())
            ->add($head = new HtmlHeadBag())
        ;
        $runtime = new HtmlDocumentRuntime($this->createAccessor($responseContext));

        $runtime->add('<meta data-first>', DocumentLocation::head);
        $runtime->add('<style>first</style>', DocumentLocation::stylesheets, ['identifier' => 'theme']);
        $runtime->add('<style>replacement</style>', DocumentLocation::stylesheets, ['identifier' => 'theme']);
        $runtime->add('<script src="/footer.js"></script>', DocumentLocation::endOfBody, ['identifier' => 'footer']);

        $tags = $head->all();

        $this->assertSame('<meta data-first>', $tags['contao.twig.head.1']);
        $this->assertSame('<style>replacement</style>', $tags['contao.twig.stylesheets.theme']);
        $this->assertSame(['footer' => '<script src="/footer.js"></script>'], $body->all());
        $this->assertArrayNotHasKey('TL_HEAD', $GLOBALS);
        $this->assertArrayNotHasKey('TL_STYLE_SHEETS', $GLOBALS);
        $this->assertArrayNotHasKey('TL_BODY', $GLOBALS);
    }

    /**
     * @param array{
     *     identifier: string|null,
     *     content: string,
     *     location: DocumentLocation,
     *     global: string,
     *     expected: array<array-key, string>,
     * } $data
     */
    #[DataProvider('provideLegacyContent')]
    public function testFallsBackToLegacyGlobalsWithoutResponseContextBags(array $data): void
    {
        $this->expectUserDeprecationMessageMatches('/Using the Twig "add" tag without the corresponding response context bag is deprecated/');

        $runtime = new HtmlDocumentRuntime($this->createStub(ResponseContextAccessor::class));
        $options = null === $data['identifier'] ? [] : ['identifier' => $data['identifier']];
        $runtime->add($data['content'], $data['location'], $options);

        $this->assertSame($data['expected'], $GLOBALS[$data['global']]);
    }

    public static function provideLegacyContent(): iterable
    {
        yield 'head' => [[
            'identifier' => 'head',
            'content' => '<meta data-head>',
            'location' => DocumentLocation::head,
            'global' => 'TL_HEAD',
            'expected' => ['head' => '<meta data-head>'],
        ]];

        yield 'stylesheets' => [[
            'identifier' => null,
            'content' => '<style></style>',
            'location' => DocumentLocation::stylesheets,
            'global' => 'TL_STYLE_SHEETS',
            'expected' => ['<style></style>'],
        ]];

        yield 'body' => [[
            'identifier' => 'body',
            'content' => '<script></script>',
            'location' => DocumentLocation::endOfBody,
            'global' => 'TL_BODY',
            'expected' => ['body' => '<script></script>'],
        ]];
    }

    private function createAccessor(ResponseContext $responseContext): ResponseContextAccessor
    {
        $accessor = $this->createStub(ResponseContextAccessor::class);
        $accessor
            ->method('getResponseContext')
            ->willReturn($responseContext)
        ;

        return $accessor;
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CommentsBundle\Util\BbCode;
use PHPUnit\Framework\TestCase;

class BbCodeTest extends TestCase
{
    /**
     * @dataProvider provideBbCode
     */
    public function testConvertToHtml(string $bbCode, string $expectedHtml): void
    {
        $GLOBALS['TL_LANG']['MSC'] = [
            'com_quote' => '%s wrote:',
            'com_code' => 'Code:',
        ];

        $this->assertSame($expectedHtml, (new BbCode())->toHtml($bbCode));

        unset($GLOBALS['TL_LANG']);
    }

    public function provideBbCode(): Generator
    {
        yield 'transforms b,i and u tags' => [
            'This should be [b]strong,[/b] [i]italic[/i] and [u]underlined[/u].',
            'This should be <strong>strong,</strong> <em>italic</em> and <span style="text-decoration: underline">underlined</span>.',
        ];

        yield 'ignores non-opened tags' => [
            'foo[/i] bar',
            'foo bar',
        ];

        yield 'ignores non-closed tags' => [
            'foo [i]bar',
            'foo bar',
        ];

        yield 'ignores nesting the same tag ' => [
            '[i]foo [i]bar[/i][/i]',
            '<em>foo bar</em>',
        ];

        yield 'resolves interleaved tags' => [
            '[i][b]foo[/i]bar[/b]',
            '<em><strong>foo</strong></em>bar',
        ];

        yield 'transforms quote tags' => [
            '[quote]See? A "quote".[/quote]',
            '<blockquote>See? A &quot;quote&quot;.</blockquote>',
        ];

        yield 'transforms quote tags with author attribute' => [
            '[quote=Someone]I\d rather have [b]markdown[/b][/quote]',
            '<blockquote><p>Someone wrote:</p>I\d rather have <strong>markdown</strong></blockquote>',
        ];

        yield 'ignores nested quotes' => [
            '[quote]A [quote]quote[/quote] of a quote[/quote]',
            '<blockquote>A quote</blockquote> of a quote',
        ];

        yield 'only allows quotes on top level' => [
            'A [b]strong [quote]statement[/quote]![/b]',
            'A <strong>strong </strong><blockquote>statement</blockquote>!',
        ];

        yield 'wraps code in pre tags' => [
            'some [code]things without [b]formatting[/b][/code]',
            'some <div class="code"><p>Code:</p><pre>things without [b]formatting[/b]</pre></div>',
        ];

        yield 'only allows code on top level' => [
            '[i][code]no italic code?[/code][/i]',
            '<div class="code"><p>Code:</p><pre>no italic code?</pre></div>',
        ];

        yield 'transforms url tags' => [
            '[url]https://example.com[/url] [url=https://example.com]my website[/url]',
            '<a href="https://example.com" rel="noopener noreferrer nofollow">https://example.com</a> <a href="https://example.com" rel="noopener noreferrer nofollow">my website</a>',
        ];

        yield 'transforms email tags' => [
            '[email]foo@contao.org[/email] [email=foo@contao.org]my email address[/email]',
            '<a href="mailto:foo@contao.org">foo@contao.org</a> <a href="mailto:foo@contao.org">my email address</a>',
        ];

        yield 'ignores invalid urls (no FQDN)' => [
            '[url=foobar]foobar[/url] [url]foo.org[/url]',
            'foobar foo.org',
        ];

        yield 'ignores invalid email addresses' => [
            '[email=foobar]foobar[/email] [email]foobar[/email]',
            'foobar foobar',
        ];

        yield 'ignores img and color tag' => [
            '[color="red"]colored[/color] [img]image[/img]',
            'colored image',
        ];

        yield 'does not treat other things in brackets as tags' => [
            '[x] Yes or [ ] No? [o][/o]',
            '[x] Yes or [ ] No? [o][/o]',
        ];

        yield 'replaces special chars' => [
            'a&b{{no}}]<>\'":',
            'a&amp;b]&lt;&gt;&apos;&quot;:',
        ];

        yield 'encodes malicious email' => [
            '[email]"/onmouseenter=alert(1)>"@contao.org[/email]',
            '<a href="mailto:&quot;/onmouseenter=alert(1)&gt;&quot;@contao.org">&quot;/onmouseenter=alert(1)&gt;&quot;@contao.org</a>',
        ];

        yield 'encodes URLs' => [
            '[url]https://example.com/foo&bar[/url]',
            '<a href="https://example.com/foo&amp;bar" rel="noopener noreferrer nofollow">https://example.com/foo&amp;bar</a>',
        ];

        yield 'encodes insert tags' => [
            '{[url]{insert_bad}[url]}',
            '&#123;&#123;insert_bad&#125;&#125;',
        ];
    }
}

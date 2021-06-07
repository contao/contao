<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class StringUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getFixturesDir());
        $container->setParameter('kernel.cache_dir', $this->getFixturesDir().'/cache');
        $container->setParameter('kernel.debug', false);
        $container->set('request_stack', new RequestStack());
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('monolog.logger.contao', new NullLogger());

        System::setContainer($container);
    }

    public function testGeneratesAliases(): void
    {
        $GLOBALS['TL_CONFIG']['characterSet'] = 'UTF-8';

        $this->assertSame('foo', StringUtil::generateAlias('foo'));
        $this->assertSame('foo', StringUtil::generateAlias('FOO'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo bar'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('%foo&bar~'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo&amp;bar'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo-{{link::12}}-bar'));
        $this->assertSame('id-123', StringUtil::generateAlias('123'));
        $this->assertSame('123foo', StringUtil::generateAlias('123foo'));
        $this->assertSame('foo123', StringUtil::generateAlias('foo123'));
    }

    public function testStripsTheRootDirectory(): void
    {
        $this->assertSame('', StringUtil::stripRootDir($this->getFixturesDir().'/'));
        $this->assertSame('', StringUtil::stripRootDir($this->getFixturesDir().'\\'));
        $this->assertSame('foo', StringUtil::stripRootDir($this->getFixturesDir().'/foo'));
        $this->assertSame('foo', StringUtil::stripRootDir($this->getFixturesDir().'\foo'));
        $this->assertSame('foo/', StringUtil::stripRootDir($this->getFixturesDir().'/foo/'));
        $this->assertSame('foo\\', StringUtil::stripRootDir($this->getFixturesDir().'\foo\\'));
        $this->assertSame('foo/bar', StringUtil::stripRootDir($this->getFixturesDir().'/foo/bar'));
        $this->assertSame('foo\bar', StringUtil::stripRootDir($this->getFixturesDir().'\foo\bar'));
        $this->assertSame('../../foo/bar', StringUtil::stripRootDir($this->getFixturesDir().'/../../foo/bar'));
    }

    public function testFailsIfThePathIsOutsideTheRootDirectory(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir('/foo');
    }

    public function testFailsIfThePathIsTheParentFolder(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir(\dirname($this->getFixturesDir()).'/');
    }

    public function testFailsIfThePathDoesNotMatch(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir($this->getFixturesDir().'foo/');
    }

    public function testFailsIfThePathEqualsTheRootDirectory(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir($this->getFixturesDir());
    }

    public function testHandlesFalseyValuesWhenDecodingEntities(): void
    {
        $this->assertSame('0', StringUtil::decodeEntities(0));
        $this->assertSame('0', StringUtil::decodeEntities('0'));
        $this->assertSame('', StringUtil::decodeEntities(''));
        $this->assertSame('', StringUtil::decodeEntities(false));
        $this->assertSame('', StringUtil::decodeEntities(null));
    }

    /**
     * @dataProvider trimsplitProvider
     */
    public function testSplitsAndTrimsStrings(string $pattern, string $string, array $expected): void
    {
        $this->assertSame($expected, StringUtil::trimsplit($pattern, $string));
    }

    public function trimsplitProvider(): \Generator
    {
        yield 'Test regular split' => [
            ',',
            'foo,bar',
            ['foo', 'bar'],
        ];

        yield 'Test split with trim' => [
            ',',
            " \n \r \t foo \n \r \t , \n \r \t bar \n \r \t ",
            ['foo', 'bar'],
        ];

        yield 'Test regex split' => [
            '[,;]',
            'foo,bar;baz',
            ['foo', 'bar', 'baz'],
        ];

        yield 'Test regex split with trim' => [
            '[,;]',
            " \n \r \t foo \n \r \t , \n \r \t bar \n \r \t ; \n \r \t baz \n \r \t ",
            ['foo', 'bar', 'baz'],
        ];

        yield 'Test split cache bug 1' => [
            ',',
            ',foo,,bar',
            ['', 'foo', '', 'bar'],
        ];

        yield 'Test split cache bug 2' => [
            ',,',
            'foo,,bar',
            ['foo', 'bar'],
        ];
    }

    /**
     * @dataProvider getRevertInputEncoding
     */
    public function testRevertInputEncoding(string $source, string $expected = null): void
    {
        Input::setGet('value', $source);
        $inputEncoded = Input::get('value');
        Input::setGet('value', null);

        // Test input encoding round trip
        $this->assertSame($expected ?? $source, StringUtil::revertInputEncoding($inputEncoded));
    }

    public function getRevertInputEncoding(): \Generator
    {
        yield ['foobar'];
        yield ['foo{{email::test@example.com}}bar'];
        yield ['{{date::...}}'];
        yield ["<>&\u{A0}<>&\u{A0}"];
        yield ['I <3 Contao'];
        yield ['Remove unexpected <span>HTML tags'];
        yield ['Keep non-HTML <tags> intact'];
        yield ['Basic [&] entities [nbsp]', "Basic & entities \u{A0}"];
        yield ["Cont\xE4o invalid UTF-8", "Cont\u{FFFD}o invalid UTF-8"];
    }

    /**
     * @dataProvider getInputEncodedToPlainText
     */
    public function testInputEncodedToPlainText(string $source, string $expected, bool $removeInsertTags = false): void
    {
        $this->assertSame($expected, StringUtil::inputEncodedToPlainText($source, $removeInsertTags));

        Input::setGet('value', $expected);
        $inputEncoded = Input::get('value');
        Input::setGet('value', null);

        // Test input encoding round trip
        $this->assertSame($expected, StringUtil::inputEncodedToPlainText($inputEncoded, true));
        $this->assertSame($expected, StringUtil::inputEncodedToPlainText($inputEncoded, false));
    }

    public function getInputEncodedToPlainText(): \Generator
    {
        yield ['foobar', 'foobar'];
        yield ['foo{{email::test@example.com}}bar', 'footest@example.combar'];
        yield ['foo{{email::test@example.com}}bar', 'foobar', true];
        yield ['{{date::...}}', '...'];
        yield ['{{date::...}}', '', true];
        yield ["&lt;&#62;&\u{A0}[lt][gt][&][nbsp]", "<>&\u{A0}<>&\u{A0}", true];
        yield ['I &lt;3 Contao', 'I <3 Contao'];
        yield ['Remove unexpected <span>HTML tags', 'Remove unexpected HTML tags'];
        yield ['Keep non-HTML &lt;tags&#62; intact', 'Keep non-HTML <tags> intact'];
        yield ["Cont\xE4o invalid UTF-8", "Cont\u{FFFD}o invalid UTF-8"];
        yield ['&#123;&#123;date&#125;&#125;', '[{]date[}]'];
    }

    /**
     * @dataProvider getHtmlToPlainText
     */
    public function testHtmlToPlainText(string $source, string $expected, bool $removeInsertTags = false): void
    {
        $this->assertSame($expected, StringUtil::htmlToPlainText($source, $removeInsertTags));

        Input::setPost('value', str_replace(['&#123;&#123;', '&#125;&#125;'], ['[{]', '[}]'], $source));
        $inputXssStripped = str_replace(['&#123;&#123;', '&#125;&#125;'], ['{{', '}}'], Input::postHtml('value', true));
        Input::setPost('value', null);

        $this->assertSame($expected, StringUtil::htmlToPlainText($inputXssStripped, $removeInsertTags));
    }

    public function getHtmlToPlainText(): \Generator
    {
        yield from $this->getInputEncodedToPlainText();

        yield ['foo<br>bar{{br}}baz', "foo\nbar\nbaz"];
        yield [" \t\r\nfoo \t\r\n \r\n\t bar \t\r\n", 'foo bar'];
        yield [" \t\r\n<br>foo \t<br>\r\n \r\n\t<br> bar <br>\t\r\n", "foo\nbar"];

        yield [
            '<h1>Headline</h1>Text<ul><li>List 1</li><li>List 2</li></ul><p>Inline<span>text</span> and <a>link</a></p><div><div><div>single newline',
            "Headline\nText\nList 1\nList 2\nInlinetext and link\nsingle newline",
        ];
    }
}

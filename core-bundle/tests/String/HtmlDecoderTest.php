<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\String;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\InsertTagSubscription;
use Contao\CoreBundle\InsertTag\Resolver\DateInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\LegacyInsertTag;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Input;
use Contao\InsertTags;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class HtmlDecoderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->method('hasFrontendUser')
            ->willReturn(false)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.security.token_checker', $tokenChecker);
        $container->set('monolog.logger.contao.error', $this->createMock(LoggerInterface::class));

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([Input::class, InsertTags::class, System::class, Config::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider getInputEncodedToPlainText
     */
    public function testInputEncodedToPlainText(string $source, string $expected, bool $removeInsertTags = false): void
    {
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $parser->addSubscription(new InsertTagSubscription(new DateInsertTag(), '__invoke', 'date', null, true, true));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'email', null, true, false));
        $htmlDecoder = new HtmlDecoder($parser);

        $this->assertSame($expected, $htmlDecoder->inputEncodedToPlainText($source, $removeInsertTags));

        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request(['value' => $expected]));

        $inputEncoded = Input::get('value');

        // Test input encoding round trip
        $this->assertSame($expected, $htmlDecoder->inputEncodedToPlainText($inputEncoded, true));
        $this->assertSame($expected, $htmlDecoder->inputEncodedToPlainText($inputEncoded));
    }

    public function getInputEncodedToPlainText(): \Generator
    {
        yield ['foobar', 'foobar'];
        yield ['foo{{email::test@example.com}}bar', 'footest@example.combar'];
        yield ['foo{{email::test@example.com}}bar', 'foobar', true];
        yield ['{{date::...}}', '...'];
        yield ['{{date::...}}', '', true];
        yield ["&lt;&#62;&\u{A0}&lt;&gt;&amp;&nbsp;", "<>&\u{A0}<>&\u{A0}", true];
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
        $parser = new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class));
        $parser->addSubscription(new InsertTagSubscription(new DateInsertTag(), '__invoke', 'date', null, true, true));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'email', null, true, false));
        $parser->addSubscription(new InsertTagSubscription(new LegacyInsertTag(System::getContainer()), '__invoke', 'br', null, true, false));
        $htmlDecoder = new HtmlDecoder($parser);

        $this->assertSame($expected, $htmlDecoder->htmlToPlainText($source, $removeInsertTags));

        System::getContainer()->set('request_stack', $stack = new RequestStack());
        $stack->push(new Request([], ['value' => str_replace(['&#123;&#123;', '&#125;&#125;'], ['[{]', '[}]'], $source)]));

        $inputXssStripped = str_replace(['&#123;&#123;', '&#125;&#125;'], ['{{', '}}'], Input::postHtml('value', true));

        $this->assertSame($expected, $htmlDecoder->htmlToPlainText($inputXssStripped, $removeInsertTags));
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

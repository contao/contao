<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Interop;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContaoEscaper;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Twig\Environment;
use Twig\Error\RuntimeError;

class ContaoEscaperTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider provideHtmlInput
     */
    public function testEscapesHtml(int|string $input, string $expectedOutput): void
    {
        $this->assertSame($expectedOutput, $this->invokeEscapeHtml($input, null), 'no charset specified');
        $this->assertSame($expectedOutput, $this->invokeEscapeHtml($input, 'UTF-8'), 'UTF-8');
        $this->assertSame($expectedOutput, $this->invokeEscapeHtml($input, 'utf-8'), 'utf-8');
    }

    public function provideHtmlInput(): \Generator
    {
        yield 'simple string' => [
            'foo',
            'foo',
        ];

        yield 'integer' => [
            123,
            '123',
        ];

        yield 'string with entities' => [
            'A & B &rarr; &#9829;',
            'A &amp; B &rarr; &#9829;',
        ];

        yield 'string with uppercase entities' => [
            '&AMP; &QUOT; &LT; &GT;',
            '&amp; &quot; &lt; &gt;',
        ];
    }

    /**
     * @dataProvider provideHtmlAttributeInput
     */
    public function testEscapesHtmlAttributes(string $input, string $expectedOutput): void
    {
        $GLOBALS['TL_HOOKS'] = ['replaceInsertTags' => [[static::class, 'executeReplaceInsertTagsCallback']]];

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('contao.insert_tag.parser', new InsertTagParser($this->createMock(ContaoFramework::class), $this->createMock(LoggerInterface::class), $this->createMock(FragmentHandler::class), $this->createMock(RequestStack::class)));

        System::setContainer($container);

        $this->assertSame($expectedOutput, $this->invokeEscapeHtmlAttr($input, null), 'no charset specified');
        $this->assertSame($expectedOutput, $this->invokeEscapeHtmlAttr($input, 'UTF-8'), 'UTF-8');
        $this->assertSame($expectedOutput, $this->invokeEscapeHtmlAttr($input, 'utf-8'), 'utf-8');

        unset($GLOBALS['TL_HOOKS']);
    }

    public function executeReplaceInsertTagsCallback(string $tag, bool $cache): string|false
    {
        if ('bar' !== $tag) {
            return false;
        }

        if ($cache) {
            $this->fail('Controller::replaceInsertTags must not be called with $blnCache = true.');
        }

        return 'baz';
    }

    public function provideHtmlAttributeInput(): \Generator
    {
        yield 'simple string' => [
            'foo',
            'foo',
        ];

        yield 'special chars and spaces' => [
            'foo:{bar}=& "baz"',
            'foo&#x3A;&#x7B;bar&#x7D;&#x3D;&amp;&#x20;&quot;baz&quot;',
        ];

        yield 'prevent double encoding' => [
            'A&amp;B',
            'A&amp;B',
        ];
    }

    public function testEscapeHtmlThrowsErrorIfCharsetIsNotUtf8(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The "contao_html" escape filter does not support the ISO-8859-1 charset, use UTF-8 instead.');

        $this->invokeEscapeHtml('foo', 'ISO-8859-1');
    }

    public function testEscapeHtmlAttrThrowsErrorIfCharsetIsNotUtf8(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The "contao_html_attr" escape filter does not support the ISO-8859-1 charset, use UTF-8 instead.');

        $this->invokeEscapeHtmlAttr('foo', 'ISO-8859-1');
    }

    private function invokeEscapeHtml(int|string $input, string|null $charset): string
    {
        return (new ContaoEscaper())->escapeHtml($this->createMock(Environment::class), $input, $charset);
    }

    private function invokeEscapeHtmlAttr(int|string $input, string|null $charset): string
    {
        return (new ContaoEscaper())->escapeHtmlAttr($this->createMock(Environment::class), $input, $charset);
    }
}

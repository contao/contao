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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContaoEscaper;
use Twig\Environment;
use Twig\Error\RuntimeError;

class ContaoEscaperTest extends TestCase
{
    /**
     * @dataProvider provideHtmlInput
     */
    public function testEscapesHtml($input, string $expectedOutput): void
    {
        $this->assertSame(
            $expectedOutput,
            $this->invokeEscapeHtml($input, null),
            'no charset specified'
        );

        $this->assertSame(
            $expectedOutput,
            $this->invokeEscapeHtml($input, 'UTF-8'),
            'UTF-8'
        );

        $this->assertSame(
            $expectedOutput,
            $this->invokeEscapeHtml($input, 'utf-8'),
            'utf-8'
        );
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
    public function testEscapesHtmlAttributes($input, array $insertTagMapping, string $expectedOutput): void
    {
        $this->assertSame(
            $expectedOutput,
            $this->invokeEscapeHtmlAttr($input, null, $insertTagMapping),
            'no charset specified'
        );

        $this->assertSame(
            $expectedOutput,
            $this->invokeEscapeHtmlAttr($input, 'UTF-8', $insertTagMapping),
            'UTF-8'
        );

        $this->assertSame(
            $expectedOutput,
            $this->invokeEscapeHtmlAttr($input, 'utf-8', $insertTagMapping),
            'utf-8'
        );
    }

    public function provideHtmlAttributeInput(): \Generator
    {
        yield 'simple string' => [
            'foo',
            [],
            'foo',
        ];

        yield 'special chars and spaces' => [
            'foo:{bar}=& "baz"',
            [],
            'foo&#x3A;&#x7B;bar&#x7D;&#x3D;&amp;&#x20;&quot;baz&quot;',
        ];

        yield 'prevent double encoding' => [
            'A&amp;B',
            [],
            'A&amp;B',
        ];

        yield 'replacing insert tags beforehand' => [
            'foo{{bar}}',
            ['{{bar}}' => 'baz'],
            'foobaz',
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

    private function invokeEscapeHtml($input, ?string $charset): string
    {
        return $this->getContaoEscaper()->escapeHtml(
            $this->createMock(Environment::class),
            $input,
            $charset
        );
    }

    private function invokeEscapeHtmlAttr($input, ?string $charset, $insertTagMapping = []): string
    {
        $controller = $this->mockAdapter(['replaceInsertTags']);
        $controller
            ->method('replaceInsertTags')
            ->willReturnCallback(
                static function ($string) use ($insertTagMapping) {
                    return str_replace(array_keys($insertTagMapping), array_values($insertTagMapping), $string);
                }
            )
        ;

        $framework = $this->mockContaoFramework([Controller::class => $controller]);

        return $this->getContaoEscaper($framework)->escapeHtmlAttr(
            $this->createMock(Environment::class),
            $input,
            $charset
        );
    }

    private function getContaoEscaper(ContaoFramework $framework = null): ContaoEscaper
    {
        if (null === $framework) {
            $framework = $this->createMock(ContaoFramework::class);
        }

        return new ContaoEscaper($framework);
    }
}

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

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\HighlighterRuntime;
use Highlight\Highlighter;

class HighlighterRuntimeTest extends TestCase
{
    /**
     * @dataProvider provideLanguageNames
     */
    public function testHighlight(string|null $languageName, string $expectedLanguageName): void
    {
        $result = new \stdClass();
        $result->relevance = 50;
        $result->value = 'the <highlighted> code';

        $highlighter = $this->createMock(Highlighter::class);
        $highlighter
            ->expects($this->once())
            ->method('highlight')
            ->with($expectedLanguageName, 'code')
            ->willReturn($result)
        ;

        $runtime = new HighlighterRuntime($highlighter);
        $result = $runtime->highlight('code', $languageName);

        $this->assertSame(50, $result->relevance);
        $this->assertSame('the <highlighted> code', $result->value);
        $this->assertSame('the <highlighted> code', (string) $result);
    }

    public function provideLanguageNames(): \Generator
    {
        yield 'unchanged default' => ['php', 'php'];

        yield 'uppercase input' => ['XML', 'xml'];

        yield 'no language specified (null)' => [null, 'plaintext'];

        yield 'no language specified (empty string)' => ['', 'plaintext'];

        yield 'C#' => ['C#', 'csharp'];

        yield 'C++' => ['C++', 'cpp'];
    }

    public function testHighlightAuto(): void
    {
        $result = new \stdClass();
        $result->relevance = 50;
        $result->value = 'the <highlighted> code';

        $highlighter = $this->createMock(Highlighter::class);
        $highlighter
            ->expects($this->once())
            ->method('highlightAuto')
            ->with('code', ['lang1', 'lang2', 'lang3'])
            ->willReturn($result)
        ;

        $runtime = new HighlighterRuntime($highlighter);
        $result = $runtime->highlightAuto('code', ['lang1', 'lang2', 'lang3']);

        $this->assertSame(50, $result->relevance);
        $this->assertSame('the <highlighted> code', $result->value);
        $this->assertSame('the <highlighted> code', (string) $result);
    }
}

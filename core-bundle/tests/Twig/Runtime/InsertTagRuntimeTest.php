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

use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;

class InsertTagRuntimeTest extends TestCase
{
    public function testRenderInsertTag(): void
    {
        $insertTags = $this->createMock(InsertTagParser::class);
        $insertTags
            ->expects($this->once())
            ->method('renderTag')
            ->with('tag')
            ->willReturn(new InsertTagResult('replaced-tag'))
        ;

        $runtime = new InsertTagRuntime($insertTags);

        $this->assertSame('replaced-tag', $runtime->renderInsertTag('tag'));
    }

    public function testReplaceInsertTags(): void
    {
        $insertTags = $this->createMock(InsertTagParser::class);
        $insertTags
            ->expects($this->once())
            ->method('replaceInline')
            ->with('foo {{tag}}')
            ->willReturn('foo replaced-tag')
        ;

        $runtime = new InsertTagRuntime($insertTags);

        $this->assertSame('foo replaced-tag', $runtime->replaceInsertTags('foo {{tag}}'));
    }

    public function testReplaceInsertTagsChunkedRaw(): void
    {
        $insertTags = $this->createMock(InsertTagParser::class);
        $insertTags
            ->expects($this->once())
            ->method('replaceChunked')
            ->with('{{tag}} foo')
            ->willReturn(new ChunkedText(['', '<replaced-tag>', ' foo']))
        ;

        $runtime = new InsertTagRuntime($insertTags);

        $this->assertSame('<replaced-tag> foo', (string) $runtime->replaceInsertTagsChunkedRaw('{{tag}} foo'));
    }
}

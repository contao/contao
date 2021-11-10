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
use Contao\CoreBundle\Twig\Interop\ChunkedText;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\InsertTags;

class InsertTagRuntimeTest extends TestCase
{
    public function testRenderInsertTag(): void
    {
        $insertTags = $this->createMock(InsertTags::class);
        $insertTags
            ->expects($this->once())
            ->method('replace')
            ->with('{{tag}}', false)
            ->willReturn('replaced-tag')
        ;

        $runtime = new InsertTagRuntime($insertTags);

        $this->assertSame('replaced-tag', $runtime->renderInsertTag('tag'));
    }

    public function testReplaceInsertTags(): void
    {
        $insertTags = $this->createMock(InsertTags::class);
        $insertTags
            ->expects($this->once())
            ->method('replace')
            ->with('foo {{tag}}', false)
            ->willReturn('foo replaced-tag')
        ;

        $runtime = new InsertTagRuntime($insertTags);

        $this->assertSame('foo replaced-tag', $runtime->replaceInsertTags('foo {{tag}}'));
    }

    public function testReplaceInsertTagsChunkedRaw(): void
    {
        $insertTags = $this->createMock(InsertTags::class);
        $insertTags
            ->expects($this->once())
            ->method('replace')
            ->with('{{tag}} foo', false, true)
            ->willReturn(new ChunkedText(['', '<replaced-tag>', ' foo']))
        ;

        $runtime = new InsertTagRuntime($insertTags);

        $this->assertSame('<replaced-tag> foo', (string) $runtime->replaceInsertTagsChunkedRaw('{{tag}} foo'));
    }
}

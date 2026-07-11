<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag;

use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\Tests\TestCase;

class ChunkedTextTest extends TestCase
{
    public function testToString(): void
    {
        $chunkedText = new ChunkedText(['foo', '<raw>', 'bar', '', 'baz']);
        $this->assertSame('foo<raw>barbaz', (string) $chunkedText);
    }

    public function testChunkTypes(): void
    {
        $chunkedText = new ChunkedText(['foo', '<raw>', 'bar', '', 'baz', '<raw2>', '', '<raw3>', '', '', '', '<raw4>']);

        $expected = [
            [ChunkedText::TYPE_TEXT, 'foo'],
            [ChunkedText::TYPE_RAW, '<raw>'],
            [ChunkedText::TYPE_TEXT, 'bar'],
            [ChunkedText::TYPE_TEXT, 'baz'],
            [ChunkedText::TYPE_RAW, '<raw2>'],
            [ChunkedText::TYPE_RAW, '<raw3>'],
            [ChunkedText::TYPE_RAW, '<raw4>'],
        ];

        $this->assertSame($expected, iterator_to_array($chunkedText));
    }

    public function testFromTypedChunks(): void
    {
        $typedChunks = [
            [ChunkedText::TYPE_TEXT, 'foo'],
            [ChunkedText::TYPE_RAW, '<raw>'],
            [ChunkedText::TYPE_TEXT, 'bar'],
            [ChunkedText::TYPE_TEXT, 'baz'],
            [ChunkedText::TYPE_TEXT, ''],
            [ChunkedText::TYPE_RAW, '<raw2>'],
            [ChunkedText::TYPE_RAW, '<raw3>'],
            [ChunkedText::TYPE_RAW, ''],
            [ChunkedText::TYPE_RAW, '<raw4>'],
            [ChunkedText::TYPE_TEXT, ''],
            [ChunkedText::TYPE_RAW, ''],
        ];

        $expected = [
            [ChunkedText::TYPE_TEXT, 'foo'],
            [ChunkedText::TYPE_RAW, '<raw>'],
            [ChunkedText::TYPE_TEXT, 'bar'],
            [ChunkedText::TYPE_TEXT, 'baz'],
            [ChunkedText::TYPE_RAW, '<raw2>'],
            [ChunkedText::TYPE_RAW, '<raw3>'],
            [ChunkedText::TYPE_RAW, '<raw4>'],
        ];

        $this->assertSame($expected, iterator_to_array(ChunkedText::fromTypedChunks($typedChunks)));
    }
}

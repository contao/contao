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

use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\ParsedSequence;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Tests\TestCase;

class ParsedSequenceTest extends TestCase
{
    public function testGetCountSerialize(): void
    {
        $insertTag = new ResolvedInsertTag('tag', new ResolvedParameters([]), []);
        $result = new InsertTagResult('result');
        $sequence = new ParsedSequence(['string', $insertTag, $result, '']);

        $this->assertCount(3, $sequence);

        $this->assertSame('string', $sequence->get(0));
        $this->assertSame($insertTag, $sequence->get(1));
        $this->assertSame($result, $sequence->get(2));

        $this->assertSame(['string', $insertTag, $result], iterator_to_array($sequence));
        $this->assertSame('string{{tag}}result', $sequence->serialize());
    }

    /**
     * @dataProvider getHasInsertTags
     */
    public function testHasInsertTag(bool $expected, array $items): void
    {
        $sequence = new ParsedSequence($items);
        $this->assertSame($expected, $sequence->hasInsertTags());

        $sequence = new ParsedSequence([...$items, ...$items]);
        $this->assertSame($expected, $sequence->hasInsertTags());
    }

    public static function getHasInsertTags(): iterable
    {
        $insertTag = new ResolvedInsertTag('tag', new ResolvedParameters([]), []);
        $result = new InsertTagResult('result');

        yield [true, ['string', $insertTag, $result]];
        yield [false, ['string', $result]];
        yield [true, [$insertTag, $result]];
        yield [true, ['string', $insertTag]];
        yield [true, [$insertTag]];
        yield [false, []];
    }
}

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

use Contao\CoreBundle\InsertTag\InsertTag;
use Contao\CoreBundle\InsertTag\InsertTagFlag;
use Contao\CoreBundle\InsertTag\ParsedInsertTag;
use Contao\CoreBundle\InsertTag\ParsedParameters;
use Contao\CoreBundle\InsertTag\ParsedSequence;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Tests\TestCase;

class InsertTagTest extends TestCase
{
    public function testGetterSetter(): void
    {
        $parameters = new ResolvedParameters([]);
        $flags = [new InsertTagFlag('flag')];
        $insertTag = new ResolvedInsertTag('name', $parameters, $flags);

        $this->assertSame('name', $insertTag->getName());
        $this->assertSame($parameters, $insertTag->getParameters());
        $this->assertSame($flags, $insertTag->getFlags());

        $parameters = new ParsedParameters([]);
        $insertTag = new ParsedInsertTag('name', $parameters, $flags);

        $this->assertSame('name', $insertTag->getName());
        $this->assertSame($parameters, $insertTag->getParameters());
        $this->assertSame($flags, $insertTag->getFlags());
    }

    /**
     * @dataProvider getSerialize
     */
    public function testSerialize(string $expected, InsertTag $insertTag): void
    {
        $this->assertSame($expected, $insertTag->serialize());
    }

    public function getSerialize(): \Generator
    {
        yield [
            '{{foo}}',
            new ResolvedInsertTag('foo', new ResolvedParameters([]), []),
        ];

        yield [
            '{{foo}}',
            new ParsedInsertTag('foo', new ParsedParameters([]), []),
        ];

        yield [
            '{{foo::param1::param2|flag1|flag2}}',
            new ResolvedInsertTag('foo', new ResolvedParameters(['param1', 'param2']), [new InsertTagFlag('flag1'), new InsertTagFlag('flag2')]),
        ];

        yield [
            '{{foo::param1::param2|flag1|flag2}}',
            new ParsedInsertTag('foo', new ParsedParameters([new ParsedSequence(['param1']), new ParsedSequence(['param2'])]), [new InsertTagFlag('flag1'), new InsertTagFlag('flag2')]),
        ];

        $inner = new ResolvedInsertTag('baz', new ResolvedParameters(['param1']), []);
        $outer = new ParsedInsertTag('bar', new ParsedParameters([new ParsedSequence([$inner])]), []);

        yield [
            '{{foo::{{bar::{{baz::param1}}}}}}',
            new ParsedInsertTag('foo', new ParsedParameters([new ParsedSequence([$outer])]), []),
        ];
    }
}

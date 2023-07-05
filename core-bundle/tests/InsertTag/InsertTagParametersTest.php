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

use Contao\CoreBundle\InsertTag\ParsedParameters;
use Contao\CoreBundle\InsertTag\ParsedSequence;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Tests\TestCase;

class InsertTagParametersTest extends TestCase
{
    public function testGetter(): void
    {
        $parameters = new ResolvedParameters([]);

        $this->assertNull($parameters->get(0));
        $this->assertNull($parameters->get('key'));

        $parameters = new ParsedParameters([]);

        $this->assertNull($parameters->get(0));
        $this->assertNull($parameters->get('key'));

        $parameters = new ResolvedParameters(['val1', 'key=val2']);

        $this->assertSame('val1', $parameters->get(0));
        $this->assertSame('val2', $parameters->get('key'));

        $parameters = new ParsedParameters([new ParsedSequence(['val1']), new ParsedSequence(['key=val2'])]);

        $this->assertSame('val1', $parameters->get(0)->get(0));
        $this->assertSame('val2', $parameters->get('key')->get(0));
    }

    public function testCastsIntegersAndFloats(): void
    {
        $parameters = new ResolvedParameters(['123', '0123', '1.23', '1.230', 'a=123', 'b=0123', 'c=1.23', 'd=1.230']);

        $this->assertSame('123', $parameters->get(0));
        $this->assertSame('0123', $parameters->get(1));
        $this->assertSame('1.23', $parameters->get(2));
        $this->assertSame('1.230', $parameters->get(3));

        $this->assertSame('123', $parameters->get('a'));
        $this->assertSame('0123', $parameters->get('b'));
        $this->assertSame('1.23', $parameters->get('c'));
        $this->assertSame('1.230', $parameters->get('d'));

        $this->assertSame(123, $parameters->getScalar(0));
        $this->assertSame('0123', $parameters->getScalar(1));
        $this->assertSame(1.23, $parameters->getScalar(2));
        $this->assertSame('1.230', $parameters->getScalar(3));

        $this->assertSame(123, $parameters->getScalar('a'));
        $this->assertSame('0123', $parameters->getScalar('b'));
        $this->assertSame(1.23, $parameters->getScalar('c'));
        $this->assertSame('1.230', $parameters->getScalar('d'));

        $this->assertSame([123, '0123', 1.23, '1.230', 'a=123', 'b=0123', 'c=1.23', 'd=1.230'], $parameters->allScalar());
        $this->assertSame([123], $parameters->allScalar('a'));
    }

    public function testNamedParameters(): void
    {
        $parameters = new ResolvedParameters(['key=value1', 'key=value2', '=empty1', '=empty2', '0=zero1', '0=zero2']);

        $this->assertSame('value1', $parameters->get('key'));
        $this->assertSame(['value1', 'value2'], $parameters->all('key'));
        $this->assertSame('empty1', $parameters->get(''));
        $this->assertSame(['empty1', 'empty2'], $parameters->all(''));
        $this->assertSame('zero1', $parameters->get('0'));
        $this->assertSame(['zero1', 'zero2'], $parameters->all('0'));
    }

    public function testNestedTags(): void
    {
        $parameters = new ParsedParameters([new ParsedSequence(['val'])]);

        $this->assertFalse($parameters->hasInsertTags());

        $parameters = new ParsedParameters([new ParsedSequence([new ResolvedInsertTag('foo', new ResolvedParameters([]), [])])]);

        $this->assertTrue($parameters->hasInsertTags());
        $this->assertSame('foo', $parameters->get(0)->get(0)->getName());

        $parameters = new ParsedParameters([new ParsedSequence(['prefix', new ResolvedInsertTag('foo', new ResolvedParameters([]), []), 'suffix'])]);

        $this->assertTrue($parameters->hasInsertTags());
        $this->assertSame('prefix', $parameters->get(0)->get(0));
        $this->assertSame('foo', $parameters->get(0)->get(1)->getName());
        $this->assertSame('suffix', $parameters->get(0)->get(2));
        $this->assertSame('::prefix{{foo}}suffix', $parameters->serialize());

        $parameters = new ParsedParameters([new ParsedSequence(['key=prefix', new ResolvedInsertTag('foo', new ResolvedParameters([]), []), 'suffix'])]);

        $this->assertTrue($parameters->hasInsertTags());
        $this->assertSame('prefix', $parameters->get('key')->get(0));
        $this->assertSame('foo', $parameters->get('key')->get(1)->getName());
        $this->assertSame('suffix', $parameters->get('key')->get(2));
        $this->assertSame('key=prefix', $parameters->get(0)->get(0));
        $this->assertSame('::key=prefix{{foo}}suffix', $parameters->serialize());
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Session\Attribute;

use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use PHPUnit\Framework\TestCase;

class ArrayAttributeBagTest extends TestCase
{
    public function testCanWriteTheOffset(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag['foo'] = 'bar';
        $bag['bar']['baz'] = 'foo';
        $bag['baz'] = [];
        $bag['baz']['foo'] = 'bar';

        $this->assertSame('bar', $bag->get('foo'));
        $this->assertSame(['baz' => 'foo'], $bag->get('bar'));
        $this->assertSame(['foo' => 'bar'], $bag->get('baz'));
    }

    public function testChecksIfTheOffsetExists(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag['foo'] = 'bar';
        $bag['bar']['baz'] = 'foo';

        $this->assertFalse(isset($bag['baz']));
    }

    public function testCanReadTheOffset(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag['foo'] = 'bar';
        $bag['bar']['baz'] = 'foo';

        $this->assertSame('bar', $bag['foo']);
        $this->assertSame(['baz' => 'foo'], $bag['bar']);
        $this->assertSame('foo', $bag['bar']['baz']);
    }

    public function testCanModifyTheOffset(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag['foo'] = 1;
        $bag['bar']['baz'] = 'foo';

        ++$bag['foo'];
        $bag['bar']['baz'] .= 'bar';

        $this->assertSame(2, $bag['foo']);
        $this->assertSame(['baz' => 'foobar'], $bag['bar']);
    }

    public function testCanUnsetTheOffset(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag->set('foo', 'bar');
        $bag->set('bar', ['baz' => 'foo']);

        unset($bag['foo'], $bag['bar']['baz']);

        $this->assertFalse($bag->has('foo'));
        $this->assertSame([], $bag->get('bar'));
    }

    public function testDoesNotReferenceValues(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag->set('foo', 'bar');
        $bag->set('bar', ['baz' => 'foo']);

        $foo = $bag['foo'];
        $foo = '';
        $baz = $bag['bar']['baz'];
        $baz = '';
        $bar = $bag['bar'];
        $bar = [];

        $this->assertSame('bar', $bag['foo']);
        $this->assertSame(['baz' => 'foo'], $bag['bar']);
    }
}

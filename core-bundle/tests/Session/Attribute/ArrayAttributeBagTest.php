<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Session\Attribute;

use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * Tests the ArrayAttributeBag class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ArrayAttributeBagTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $adapter = new ArrayAttributeBag(new AttributeBag('foobar_storageKey'));

        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', $adapter);
        $this->assertInstanceOf('ArrayAccess', $adapter);
    }

    /**
     * Tests the offsetSet() method.
     */
    public function testCanWriteTheOffset()
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

    /**
     * Tests the offsetExists() method.
     */
    public function testChecksIfTheOffsetExists()
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 'bar';
        $bag['bar']['baz'] = 'foo';

        $this->assertTrue(isset($bag['foo']));
        $this->assertTrue(isset($bag['bar']['baz']));
        $this->assertFalse(isset($bag['baz']));
    }

    /**
     * Tests the offsetGet() method.
     */
    public function testCanReadTheOffset()
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 'bar';
        $bag['bar']['baz'] = 'foo';

        $this->assertSame('bar', $bag['foo']);
        $this->assertSame(['baz' => 'foo'], $bag['bar']);
        $this->assertSame('foo', $bag['bar']['baz']);
    }

    /**
     * Tests the offsetGet() method.
     */
    public function testCanModifyTheOffset()
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 1;
        $bag['bar']['baz'] = 'foo';

        ++$bag['foo'];
        $bag['foo'] += 1;
        $bag['bar']['baz'] .= 'bar';

        $this->assertSame(3, $bag['foo']);
        $this->assertSame(['baz' => 'foobar'], $bag['bar']);
    }

    /**
     * Tests the offsetUnset() method.
     */
    public function testCanUnsetTheOffset()
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag->set('foo', 'bar');
        $bag->set('bar', ['baz' => 'foo']);

        unset($bag['foo'], $bag['bar']['baz']);

        $this->assertFalse($bag->has('foo'));
        $this->assertSame([], $bag->get('bar'));
    }

    /**
     * Tests that values are not referenced.
     */
    public function testDoesNotReferenceValues()
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

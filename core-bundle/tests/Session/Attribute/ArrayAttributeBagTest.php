<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Session\Attribute;

use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Test\TestCase;
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
    public function testInstantiation()
    {
        $adapter = new ArrayAttributeBag(new AttributeBag('foobar_storageKey'));

        $this->assertInstanceOf('Contao\\CoreBundle\\Session\\Attribute\\ArrayAttributeBag', $adapter);
        $this->assertInstanceOf('ArrayAccess', $adapter);
    }

    /**
     * Tests the offsetSet() method.
     */
    public function testOffsetSet()
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 'bar';

        $this->assertSame('bar', $bag->get('foo'));
    }

    /**
     * Tests the offsetExists() method.
     */
    public function testOffsetExists()
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 'bar';

        $this->assertTrue(isset($bag['foo']));
    }

    /**
     * Tests the offsetGet() method.
     */
    public function testOffsetGet()
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 'bar';

        $this->assertSame('bar', $bag['foo']);
    }

    /**
     * Tests the offsetUnset() method.
     */
    public function testOffsetUnset()
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag->set('foo', 'bar');

        unset($bag['foo']);

        $this->assertFalse($bag->has('foo'));
    }
}

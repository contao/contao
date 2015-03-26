<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Session\Attribute;

use Contao\CoreBundle\Session\Attribute\AttributeBagAdapter;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * Tests the AttributeBagAdapter class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AttributeBagAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagAdapter($attributeBag);

        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\AttributeBagAdapter', $mirror);
        $this->assertInstanceOf('ArrayAccess', $mirror);
    }

    /**
     * Tests offsetSet.
     */
    public function testOffsetSet()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagAdapter($attributeBag);

        $mirror['foo'] = 'bar';

        $this->assertSame('bar', $attributeBag->get('foo'));
    }

    /**
     * Tests offsetExists.
     */
    public function testOffsetExists()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagAdapter($attributeBag);

        $mirror['foo'] = 'bar';

        $this->assertTrue(isset($mirror['foo']));
    }

    /**
     * Tests offsetGet.
     */
    public function testOffsetGet()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagAdapter($attributeBag);

        $attributeBag->set('foo', 'bar');

        $this->assertSame('bar', $mirror['foo']);
    }

    /**
     * Tests offsetUnset.
     */
    public function testOffsetUnset()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagAdapter($attributeBag);

        $attributeBag->set('foo', 'bar');

        unset($mirror['foo']);

        $this->assertFalse($attributeBag->has('foo'));
    }
}

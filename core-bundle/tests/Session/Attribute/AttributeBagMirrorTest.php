<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Session\Attribute;
use Contao\CoreBundle\Session\Attribute\AttributeBagMirror;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * Tests the AttributeBagMirror class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AttributeBagMirrorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagMirror($attributeBag);

        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\AttributeBagMirror', $mirror);
        $this->assertInstanceOf('ArrayAccess', $mirror);
    }

    /**
     * Tests offsetSet.
     */
    public function testOffsetSet()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagMirror($attributeBag);

        $mirror['foo'] = 'bar';

        $this->assertSame('bar', $attributeBag->get('foo'));
    }

    /**
     * Tests offsetExists.
     */
    public function testOffsetExists()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagMirror($attributeBag);

        $mirror['foo'] = 'bar';

        $this->assertTrue(isset($mirror['foo']));
    }

    /**
     * Tests offsetGet.
     */
    public function testOffsetGet()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagMirror($attributeBag);

        $attributeBag->set('foo', 'bar');

        $this->assertSame('bar', $mirror['foo']);
    }

    /**
     * Tests offsetUnset.
     */
    public function testOffsetUnset()
    {
        $attributeBag = new AttributeBag('foobar_storageKey');
        $mirror = new AttributeBagMirror($attributeBag);

        $attributeBag->set('foo', 'bar');

        unset($mirror['foo']);

        $this->assertFalse($attributeBag->has('foo'));
    }
}

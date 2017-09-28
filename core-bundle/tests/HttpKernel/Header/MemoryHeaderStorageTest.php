<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\HttpKernel\Header;

use Contao\CoreBundle\HttpKernel\Header\MemoryHeaderStorage;
use Contao\CoreBundle\Tests\TestCase;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class MemoryHeaderStorageTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $storage = new MemoryHeaderStorage();

        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Header\MemoryHeaderStorage', $storage);
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Header\HeaderStorageInterface', $storage);
    }

    /**
     * Tests that all headers are returned.
     */
    public function testReturnsAllHeaders()
    {
        $storage = new MemoryHeaderStorage(['Foo: Bar']);

        $this->assertSame(['Foo: Bar'], $storage->all());

        $storage->add('Bar: Baz');

        $this->assertSame(['Foo: Bar', 'Bar: Baz'], $storage->all());
    }

    /**
     * Tests that existing headers are cleared.
     */
    public function testClearsExistingHeaders()
    {
        $storage = new MemoryHeaderStorage(['Foo: Bar']);

        $this->assertSame(['Foo: Bar'], $storage->all());

        $storage->clear();

        $this->assertSame([], $storage->all());
    }
}

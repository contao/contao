<?php

declare(strict_types=1);

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

class MemoryHeaderStorageTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $storage = new MemoryHeaderStorage();

        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Header\MemoryHeaderStorage', $storage);
        $this->assertInstanceOf('Contao\CoreBundle\HttpKernel\Header\HeaderStorageInterface', $storage);
    }

    /**
     * Tests that all headers are returned.
     */
    public function testReturnsAllHeaders(): void
    {
        $storage = new MemoryHeaderStorage(['Foo: Bar']);

        $this->assertSame(['Foo: Bar'], $storage->all());

        $storage->add('Bar: Baz');

        $this->assertSame(['Foo: Bar', 'Bar: Baz'], $storage->all());
    }

    /**
     * Tests that existing headers are cleared.
     */
    public function testClearsExistingHeaders(): void
    {
        $storage = new MemoryHeaderStorage(['Foo: Bar']);

        $this->assertSame(['Foo: Bar'], $storage->all());

        $storage->clear();

        $this->assertSame([], $storage->all());
    }
}

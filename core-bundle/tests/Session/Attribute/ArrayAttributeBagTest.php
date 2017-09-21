<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Session\Attribute;

use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

class ArrayAttributeBagTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $adapter = new ArrayAttributeBag(new AttributeBag('foobar_storageKey'));

        $this->assertInstanceOf('Contao\CoreBundle\Session\Attribute\ArrayAttributeBag', $adapter);
        $this->assertInstanceOf('ArrayAccess', $adapter);
    }

    public function testCanWriteTheOffset(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 'bar';

        $this->assertSame('bar', $bag->get('foo'));
    }

    public function testChecksIfTheOffsetExists(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 'bar';

        $this->assertTrue(isset($bag['foo']));
    }

    public function testCanReadTheOffset(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');

        $bag['foo'] = 'bar';

        $this->assertSame('bar', $bag['foo']);
    }

    public function testCanUnsetTheOffset(): void
    {
        $bag = new ArrayAttributeBag('foobar_storageKey');
        $bag->set('foo', 'bar');

        unset($bag['foo']);

        $this->assertFalse($bag->has('foo'));
    }
}

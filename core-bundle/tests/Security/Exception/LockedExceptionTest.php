<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Exception;

use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\CoreBundle\Tests\TestCase;

class LockedExceptionTest extends TestCase
{
    public function testReturnsTheLockedSecondsAndMinutes(): void
    {
        $exception = new LockedException(300);

        $this->assertSame(300, $exception->getLockedSeconds());
        $this->assertSame(5, $exception->getLockedMinutes());
    }

    public function testSerializesItself(): void
    {
        $exception = new LockedException(300, 'foobar');
        $serialized = $exception->serialize();

        if (false !== strpos($serialized, '"a:2:{')) {
            $expected = serialize([300, serialize([null, serialize([null, 0, 'foobar', __FILE__, 30])])]);
        } else {
            $expected = serialize([300, [null, [null, 0, 'foobar', __FILE__, 30]]]);
        }

        $this->assertSame($expected, $serialized);

        $exception = new LockedException(0);

        $this->assertSame(0, $exception->getLockedSeconds());
        $this->assertSame('', $exception->getMessage());

        $exception->unserialize($serialized);

        $this->assertSame(300, $exception->getLockedSeconds());
        $this->assertSame('foobar', $exception->getMessage());
    }
}

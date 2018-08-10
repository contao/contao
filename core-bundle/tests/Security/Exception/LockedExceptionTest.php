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
    public function testCanBeInstantiated(): void
    {
        $exception = new LockedException(300);

        $this->assertInstanceOf('Contao\CoreBundle\Security\Exception\LockedException', $exception);
    }

    public function testReturnsTheLockedSecondsAndMinutes(): void
    {
        $exception = new LockedException(300);

        $this->assertSame(300, $exception->getLockedSeconds());
        $this->assertSame(5, $exception->getLockedMinutes());
    }

    public function testSerializesItself(): void
    {
        $exception = new LockedException(300, 'foobar');

        $this->assertSame($this->getSerializedException(), $exception->serialize());
    }

    public function testUnserializesItself(): void
    {
        $exception = new LockedException(0);

        $this->assertSame(0, $exception->getLockedSeconds());
        $this->assertSame('', $exception->getMessage());

        $exception->unserialize($this->getSerializedException());

        $this->assertSame(300, $exception->getLockedSeconds());
        $this->assertSame('foobar', $exception->getMessage());
    }

    /**
     * Returns the serialized exception.
     */
    private function getSerializedException(): string
    {
        return serialize([300, serialize([null, serialize([null, 0, 'foobar', __FILE__, 37])])]);
    }
}

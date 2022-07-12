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

use Contao\BackendUser;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\CoreBundle\Tests\TestCase;

class LockedExceptionTest extends TestCase
{
    public function testReturnsTheLockedSecondsAndMinutes(): void
    {
        $exception = new LockedException(300);

        $this->assertSame(300, $exception->getLockedSeconds());
    }

    public function testSerializesItself(): void
    {
        $user = $this->createMock(BackendUser::class);

        $exception = new LockedException(300, 'foobar');
        $exception->setUser($user);

        $serialized = $exception->__serialize();
        $expected = [300, [$user, [null, 0, 'foobar', __FILE__, 32]]];

        $this->assertSame($expected, $serialized);

        $exception = new LockedException(0);

        $this->assertSame(0, $exception->getLockedSeconds());
        $this->assertSame('', $exception->getMessage());

        $exception->__unserialize($serialized);

        $this->assertSame(300, $exception->getLockedSeconds());
        $this->assertSame('foobar', $exception->getMessage());
    }
}

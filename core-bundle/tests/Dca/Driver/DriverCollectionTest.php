<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Dca\Driver;

use Contao\CoreBundle\Dca\Driver\DriverCollection;
use Contao\CoreBundle\Dca\Driver\DriverInterface;
use PHPUnit\Framework\TestCase;

class DriverCollectionTest extends TestCase
{
    public function testFindsDriverForResource(): void
    {
        $driverA = $this->createMock(DriverInterface::class);
        $driverA
            ->method('handles')
            ->willReturnCallback(static fn (string $resource): bool => 'tl_foo' === $resource)
        ;

        $driverB = $this->createMock(DriverInterface::class);
        $driverB
            ->method('handles')
            ->willReturnCallback(static fn (string $resource): bool => 'tl_bar' === $resource)
        ;

        $collection = new DriverCollection([
            $driverA,
            $driverB,
        ]);

        $this->assertSame($driverA, $collection->getDriverForResource('tl_foo'));
        $this->assertSame($driverB, $collection->getDriverForResource('tl_bar'));
    }

    public function testThrowsExceptionForMissingDriver(): void
    {
        $collection = new DriverCollection([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('tl_bar');

        $collection->getDriverForResource('tl_bar');
    }

    public function testChecksIfDriverExistsForResource(): void
    {
        $driverA = $this->createMock(DriverInterface::class);
        $driverA
            ->method('handles')
            ->willReturnCallback(static fn (string $resource): bool => 'tl_foo' === $resource)
        ;

        $driverB = $this->createMock(DriverInterface::class);
        $driverB
            ->method('handles')
            ->willReturnCallback(static fn (string $resource): bool => 'tl_bar' === $resource)
        ;

        $collection = new DriverCollection([
            $driverA,
            $driverB,
        ]);

        $this->assertTrue($collection->hasDriverForResource('tl_foo'));
        $this->assertTrue($collection->hasDriverForResource('tl_bar'));
        $this->assertFalse($collection->hasDriverForResource('tl_baz'));
    }
}

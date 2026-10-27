<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Version;

use Contao\ApiBundle\Version\ApiVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApiVersionTest extends TestCase
{
    public function testRepresentsACalendarDateInUtc(): void
    {
        $version = new ApiVersion('2024-02-29');

        $this->assertSame('2024-02-29', (string) $version);
        $this->assertSame('2024-02-29T00:00:00+00:00', $version->toDateTime()->format('c'));
        $this->assertTrue($version->isBefore('2024-03-01'));
        $this->assertFalse($version->isBefore('2024-02-29'));
        $this->assertFalse($version->isBefore('2024-02-28'));
        $this->assertSame('2024-02-29', (string) new ApiVersion('2024-02-29'));
    }

    #[DataProvider('provideInvalidDates')]
    public function testRejectsInvalidDates(string $date): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ApiVersion($date);
    }

    public static function provideInvalidDates(): iterable
    {
        yield [''];
        yield ['today'];
        yield ['2026-2-03'];
        yield ['2026-02-30'];
        yield ['2025-02-29'];
        yield ['2026-13-01'];
        yield ['0000-01-01'];
        yield ['2024-02-29T00:00:00Z'];
        yield ['2024-02-29,2026-10-18'];
        yield ["2024-02-29\n"];
        yield ["2024-02-29\0"];
    }
}

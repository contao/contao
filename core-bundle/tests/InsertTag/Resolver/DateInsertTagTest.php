<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\InsertTag\Resolver;

use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\InsertTag\Resolver\DateInsertTag;
use PHPUnit\Framework\TestCase;

class DateInsertTagTest extends TestCase
{
    /**
     * @dataProvider expiresAtProvider
     */
    public function testExpiresAt(string $format, \DateTimeImmutable|null $expectedExpiresAt): void
    {
        $insertTag = new DateInsertTag();
        $result = $insertTag(new ResolvedInsertTag('date', new ResolvedParameters([$format]), []));
        $this->assertSame($expectedExpiresAt?->getTimestamp(), $result->getExpiresAt()?->getTimestamp());
    }

    public function expiresAtProvider(): \Generator
    {
        yield 'Null when using only using uncacheable format characters' => [
            'H:i:s',
            null,
        ];

        yield 'Null when combining with uncacheable format characters' => [
            'd.m.Y H:i:s',
            null,
        ];

        yield 'Cacheable until the end of the year' => [
            'Y',
            new \DateTimeImmutable('last day of December this year 23:59:59'),
        ];

        yield 'Cacheable until the end of the month (Y-m)' => [
            'Y-m',
            new \DateTimeImmutable('last day of this month 23:59:59'),
        ];

        yield 'Cacheable until the end of the month (m.Y)' => [
            'm.Y',
            new \DateTimeImmutable('last day of this month 23:59:59'),
        ];

        yield 'Cacheable until the end of the day (d.m.Y)' => [
            'd.m.Y',
            new \DateTimeImmutable('today 23:59:59'),
        ];

        yield 'Cacheable until the end of the day (Y-d-m)' => [
            'Y-d-m',
            new \DateTimeImmutable('today 23:59:59'),
        ];
    }
}

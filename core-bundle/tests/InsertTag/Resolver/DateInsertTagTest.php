<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\InsertTag\Resolver;

use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\InsertTag\Resolver\DateInsertTag;
use Contao\Date;
use Contao\TestCase\ContaoTestCase;

class DateInsertTagTest extends ContaoTestCase
{
    /**
     * @dataProvider expiresAtProvider
     */
    public function testExpiresAt(array $formats, \DateTimeImmutable|null $expectedExpiresAt): void
    {
        $dateAdapter = $this->mockAdapter(['parse']);
        $dateAdapter
            ->method('parse')
            ->willReturn('parsed')
        ;

        $insertTag = new DateInsertTag($this->mockContaoFramework([Date::class => $dateAdapter]));

        foreach ($formats as $format) {
            $result = $insertTag(new ResolvedInsertTag('date', new ResolvedParameters([$format]), []));
            $this->assertSame($expectedExpiresAt?->getTimestamp(), $result->getExpiresAt()?->getTimestamp());
        }
    }

    public function expiresAtProvider(): \Generator
    {
        yield 'Null when using only using uncacheable format characters' => [
            ['H:i:s', 's', 'H:i'],
            null,
        ];

        yield 'Null when combining with uncacheable format characters' => [
            ['d.m.Y H:i:s', 'Y H'],
            null,
        ];

        yield 'Cacheable until the end of the year' => [
            ['Y', 'y'],
            new \DateTimeImmutable('last day of December this year 23:59:59'),
        ];

        yield 'Cacheable until the end of the month' => [
            ['Y-m', 'm.Y', 'n Y', 'F Y', 'M Y'],
            new \DateTimeImmutable('last day of this month 23:59:59'),
        ];

        yield 'Cacheable until the end of the day' => [
            ['d.m.Y', 'Y-d-m', 'l Y', 'D Y', 'j Y'],
            new \DateTimeImmutable('today 23:59:59'),
        ];
    }
}

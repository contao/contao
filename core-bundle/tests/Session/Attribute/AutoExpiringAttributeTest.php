<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Session\Attribute;

use Contao\CoreBundle\Session\Attribute\AutoExpiringAttribute;
use PHPUnit\Framework\TestCase;

class AutoExpiringAttributeTest extends TestCase
{
    /**
     * @dataProvider isExpiredProvider
     */
    public function testIsExpiredCalculation(\DateTime $createdAt, \DateTime $now, int $ttl, bool $shouldBeExpired): void
    {
        $attribute = new AutoExpiringAttribute($ttl, 'foobar', $createdAt);

        $this->assertSame($shouldBeExpired, $attribute->isExpired($now));
    }

    public function isExpiredProvider(): \Generator
    {
        yield '5 seconds TTL, should be marked as expired after 10 seconds' => [
            new \DateTime(),
            new \DateTime('+10 seconds'),
            5,
            true,
        ];

        yield '10 seconds TTL, should not be marked as expired after 10 seconds' => [
            new \DateTime(),
            new \DateTime('+10 seconds'),
            10,
            false,
        ];

        yield '11 seconds TTL, should not be marked as expired after 10 seconds' => [
            new \DateTime(),
            new \DateTime('+10 seconds'),
            11,
            false,
        ];
    }
}

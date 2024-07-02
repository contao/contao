<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\HttpCache;

use Contao\CoreBundle\EventListener\HttpCache\StripCookiesSubscriber;
use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StripCookiesSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $subscriber = new StripCookiesSubscriber();

        $this->assertSame([Events::PRE_HANDLE => 'preHandle'], $subscriber::getSubscribedEvents());
    }

    /**
     * @dataProvider cookiesProvider
     */
    public function testCookiesAreStrippedCorrectly(array $cookies, array $expectedCookies, array $allowList = [], array $removeFromDenyList = []): void
    {
        $request = Request::create('/', 'GET', [], $cookies);
        $event = new CacheEvent($this->createMock(CacheInvalidation::class), $request);

        $subscriber = new StripCookiesSubscriber($allowList);
        $subscriber->removeFromDenyList($removeFromDenyList);
        $subscriber->preHandle($event);

        $this->assertSame($expectedCookies, $request->cookies->all());
    }

    public static function cookiesProvider(): iterable
    {
        yield [
            ['PHPSESSID' => 'foobar', 'my_cookie' => 'value'],
            ['PHPSESSID' => 'foobar', 'my_cookie' => 'value'],
        ];

        yield [
            ['PHPSESSID' => 'foobar', '_ga' => 'value', '_pk_ref' => 'value', '_pk_hsr' => 'value'],
            ['PHPSESSID' => 'foobar'],
        ];

        yield [
            ['PHPSESSID' => 'foobar', '_gac_58168352' => 'value', 'myModal1' => 'closed'],
            ['PHPSESSID' => 'foobar'],
        ];

        yield [
            ['PHPSESSID' => 'foobar', '_gac_58168352' => 'value'],
            ['PHPSESSID' => 'foobar'],
            ['PHPSESSID'],
        ];

        yield [
            ['PHPSESSID' => 'foobar', '_ga' => 'value', '_pk_ref' => 'value', '_pk_hsr' => 'value'],
            ['PHPSESSID' => 'foobar', '_ga' => 'value'],
            [],
            ['_ga'],
        ];

        yield [
            ['PHPSESSID' => 'foobar', 'bimodal_transport' => 'value', 'modal_123_closed' => 'value'],
            ['PHPSESSID' => 'foobar', 'bimodal_transport' => 'value'],
            [],
            ['bimodal_.*'],
        ];
    }
}

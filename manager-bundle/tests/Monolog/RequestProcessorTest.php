<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Monolog;

use Contao\ManagerBundle\Monolog\RequestProcessor;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RequestProcessorTest extends ContaoTestCase
{
    /**
     * @dataProvider logExtrasProvider
     */
    public function testAddsLogExtras(string $uri, string $method): void
    {
        $request = Request::create($uri, $method);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);

        $processor = new RequestProcessor();
        $processor->onKernelRequest($event);

        $record = ['extra' => []];
        $record = $processor($record);

        $this->assertSame($uri, $record['extra']['request_uri']);
        $this->assertSame($method, $record['extra']['request_method']);
    }

    /**
     * @dataProvider logExtrasProvider
     */
    public function testIgnoresSubRequests(string $uri, string $method): void
    {
        $request = Request::create($uri, $method);
        $event = new RequestEvent($this->createMock(KernelInterface::class), $request, HttpKernelInterface::SUB_REQUEST);

        $processor = new RequestProcessor();
        $processor->onKernelRequest($event);

        $record = ['extra' => []];
        $record = $processor($record);

        $this->assertArrayNotHasKey('request_uri', $record['extra']);
        $this->assertArrayNotHasKey('request_method', $record['extra']);
    }

    /**
     * @dataProvider logExtrasProvider
     */
    public function testIgnoresIfRequestIsNotSet(): void
    {
        $processor = new RequestProcessor();

        $record = ['extra' => []];
        $record = $processor($record);

        $this->assertArrayNotHasKey('request_uri', $record['extra']);
        $this->assertArrayNotHasKey('request_method', $record['extra']);
    }

    public static function logExtrasProvider(): iterable
    {
        yield [
            'https://example.com/foo/bar',
            'GET',
        ];

        yield [
            'https://example.com/foo/bar?bar=baz',
            'GET',
        ];

        yield [
            'https://example.com/foo/bar',
            'POST',
        ];
    }
}

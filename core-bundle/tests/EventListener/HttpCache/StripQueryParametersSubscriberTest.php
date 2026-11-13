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

use Contao\CoreBundle\EventListener\HttpCache\StripQueryParametersSubscriber;
use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class StripQueryParametersSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $subscriber = new StripQueryParametersSubscriber();

        $this->assertSame(
            [
                Events::PRE_HANDLE => 'preHandle',
                Events::POST_HANDLE => 'postHandle',
            ],
            $subscriber::getSubscribedEvents(),
        );
    }

    /**
     * @dataProvider queryParametersProvider
     */
    public function testQueryParametersAreStrippedCorrectly(array $parameters, array $expectedParameters, array $allowList = [], array $removeFromDenyList = []): void
    {
        $request = Request::create('/', 'GET', $parameters);
        $event = new CacheEvent($this->createStub(CacheInvalidation::class), $request);

        $subscriber = new StripQueryParametersSubscriber($allowList);
        $subscriber->removeFromDenyList($removeFromDenyList);
        $subscriber->preHandle($event);

        $this->assertSame($expectedParameters, $request->query->all());
        $this->assertSame(array_map(\strval(...), $expectedParameters), HeaderUtils::parseQuery($request->server->get('QUERY_STRING')));
    }

    public static function queryParametersProvider(): iterable
    {
        yield [
            ['page' => 42, 'query' => 'foobar'],
            ['page' => 42, 'query' => 'foobar'],
        ];

        yield [
            ['page' => 42, 'query' => 'foobar', 'gclid' => 'EAIaIQobChMIgrbRrZLH6AIVl6F7Ch2NMQCxEAEYASAAEgLjlPD_BwE'],
            ['page' => 42, 'query' => 'foobar'],
        ];

        yield [
            ['page' => 42, 'query' => 'foobar', 'utm_source' => 'twitter'],
            ['page' => 42, 'query' => 'foobar'],
        ];

        yield [
            ['page' => 42, 'query' => 'foobar', 'utm_source' => 'twitter'],
            ['page' => 42],
            ['page'],
        ];

        yield [
            ['page' => 42, 'gclid' => 'EAIaIQobChMIgrbRrZLH6AIVl6F7Ch2NMQCxEAEYASAAEgLjlPD_BwE', 'utm_source' => 'twitter'],
            ['page' => 42, 'utm_source' => 'twitter'],
            [],
            ['utm_[a-z]+'],
        ];

        yield [
            ['page' => 42, 'utm_foo' => 'foo', 'utm_bar' => 'bar'],
            ['page' => 42, 'utm_foo' => 'foo'],
            [],
            ['utm_fo+'],
        ];
    }

    public function testRemovedQueryParametersSurviveAClonedRequest(): void
    {
        $request = Request::create('/?page=42&utm_source=twitter&gclid=abc');
        $subscriber = new StripQueryParametersSubscriber();
        $kernel = $this->createStub(CacheInvalidation::class);

        $subscriber->preHandle(new CacheEvent($kernel, $request));

        $response = new RedirectResponse('/de/?utm_source=redirect&foo=bar#content');
        $subscriber->postHandle(new CacheEvent($kernel, clone $request, $response));

        $this->assertSame('/de/?utm_source=redirect&foo=bar&gclid=abc#content', $response->headers->get('Location'));
        $this->assertSame('/de/?utm_source=redirect&foo=bar&gclid=abc#content', $response->getTargetUrl());
    }

    public function testCachedRedirectPreservesRemovedQueryParameters(): void
    {
        $backend = new class() implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                throw new \LogicException('The cached redirect should be used.');
            }
        };

        $store = $this->createStub(StoreInterface::class);
        $store
            ->method('lookup')
            ->willReturn(new Response('', 301, ['Location' => '/de/', 'Cache-Control' => 'public, max-age=600']))
        ;

        $cache = new class($backend, $store) extends HttpCache implements CacheInvalidation {
            use EventDispatchingHttpCache;

            public function fetch(Request $request, bool $catch = false): Response
            {
                return parent::fetch($request, $catch);
            }
        };

        $cache->addSubscriber(new StripQueryParametersSubscriber());

        $response = $cache->handle(Request::create('/?utm_source=twitter'));

        $this->assertSame('/de/?utm_source=twitter', $response->headers->get('Location'));
    }
}

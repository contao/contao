<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\Messenger\Message\SearchIndexMessage;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class SearchIndexListenerTest extends TestCase
{
    #[DataProvider('getRequestResponse')]
    public function testIndexesOrDeletesTheDocument(Request $request, Response $response, int $features, bool $index, bool $delete): void
    {
        $dispatchCount = (int) $index + (int) $delete;
        $messenger = $this->createMock(MessageBusInterface::class);

        $messenger
            ->expects($this->exactly($dispatchCount))
            ->method('dispatch')
            ->with($this->callback(
                function (SearchIndexMessage $message) use ($index, $delete) {
                    $this->assertSame($index, $message->shouldIndex());
                    $this->assertSame($delete, $message->shouldDelete());

                    return true;
                },
            ))
            ->willReturnCallback(static fn (SearchIndexMessage $message) => new Envelope($message))
        ;

        $event = new TerminateEvent($this->createMock(HttpKernelInterface::class), $request, $response);

        $listener = new SearchIndexListener($messenger, '_fragment', '/contao', $features);
        $listener($event);
    }

    public function testRateLimitingOnIndex(): void
    {
        $request = Request::create('/foobar');
        $response = new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"noSearch":false,"protected":false,"groups":[],"fePreview":false}</script></body></html>');

        $messenger = $this->createMock(MessageBusInterface::class);
        $messenger
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->with($this->callback(
                function (SearchIndexMessage $message) {
                    $this->assertTrue($message->shouldIndex());
                    $this->assertFalse($message->shouldDelete());

                    return true;
                },
            ))
            ->willReturnCallback(static fn (SearchIndexMessage $message) => new Envelope($message))
        ;

        $event = new TerminateEvent($this->createMock(HttpKernelInterface::class), $request, $response);
        $listener = new SearchIndexListener($messenger, '_fragment', '/contao', SearchIndexListener::FEATURE_INDEX);

        // Should index (total expected count: 1)
        $listener($event);

        // Should index because no rate limiter configured (total expected count: 2)
        $listener($event);

        // Now configure the listener with the rate limiter
        $rateLimiter = new RateLimiterFactory(
            [
                'id' => 'contao.listener.search_index.default_rate_limiter',
                'policy' => 'fixed_window',
                'limit' => 1,
                'interval' => '5 minutes',
            ],
            new InMemoryStorage(),
        );

        $listener = new SearchIndexListener($messenger, '_fragment', '/contao', SearchIndexListener::FEATURE_INDEX, $rateLimiter);

        // Should index because the rate limiter sees this response for the first time
        // (total expected count: 3)
        $listener($event);

        // Should NOT index because the rate limiter limits (total expected count: 3)
        $listener($event);
    }

    public static function getRequestResponse(): iterable
    {
        yield 'Should index because the response was successful and contains ld+json information' => [
            Request::create('/foobar'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"searchIndexer":"","protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            true,
            false,
        ];

        yield 'Should not index because even though the response was successful and contains ld+json information, it was disabled by the feature flag' => [
            Request::create('/foobar'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"searchIndexer":"","protected":false,"groups":[],"fePreview":false}</script></body></html>'),
            SearchIndexListener::FEATURE_DELETE,
            false,
            false,
        ];

        yield 'Should be skipped because it is not a GET request' => [
            Request::create('/foobar', 'POST'),
            new Response('', Response::HTTP_INTERNAL_SERVER_ERROR),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be skipped because it was requested by our own crawler' => [
            Request::create('/foobar', 'GET', [], [], [], ['HTTP_USER_AGENT' => Factory::USER_AGENT]),
            new Response(),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be skipped because it was a redirect' => [
            Request::create('/foobar'),
            new RedirectResponse('https://somewhere.else'),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be skipped because it is a fragment request' => [
            Request::create('/_fragment/foo/bar'),
            new Response(),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be skipped because it is a contao backend request' => [
            Request::create('/contao?do=article'),
            new Response(),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should be deleted because the response was "not found" (404)' => [
            Request::create('/foobar'),
            new Response('', 404),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];

        yield 'Should be deleted because the response was "gone" (410)' => [
            Request::create('/foobar'),
            new Response('', 404),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];

        yield 'Should not be deleted because even though the response was "not found" (404), it was disabled by the feature flag' => [
            Request::create('/foobar'),
            new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"searchIndexer":"","protected":false,"groups":[],"fePreview":false}</script></body></html>', 404),
            SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        $response = new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"searchIndexer":"","protected":false,"groups":[],"fePreview":false}</script></body></html>', 200);
        $response->headers->set('X-Robots-Tag', 'noindex');

        yield 'Should not index but should delete because the X-Robots-Tag header contains "noindex"' => [
            Request::create('/foobar'),
            $response,
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];

        $response = new Response('<html><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"searchIndexer":"","protected":false,"groups":[],"fePreview":false}</script></body></html>', 500);
        $response->headers->set('X-Robots-Tag', 'noindex');

        yield 'Should not index and delete because the X-Robots-Tag header contains "noindex" and response is unsuccessful' => [
            Request::create('/foobar'),
            $response,
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        yield 'Should index and not delete because searchIndexer is set to "always_index"' => [
            Request::create('/foobar'),
            new Response('<html><head><meta name="robots" content="noindex,nofollow"/></head><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@graph":[{"@type":"Page","pageId":2,"searchIndexer":"always_index","protected":false,"groups":[],"fePreview":false}]}</script></body></html>'),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            true,
            false,
        ];

        yield 'Should not index but should delete because searchIndexer is set to "never_index"' => [
            Request::create('/foobar'),
            new Response('<html><head><meta name="robots" content="index,nofollow"/></head><body><script type="application/ld+json">{"@context":"https:\/\/schema.contao.org\/","@graph":[{"@type":"Page","pageId":2,"searchIndexer":"never_index","protected":false,"groups":[],"fePreview":false}]}</script></body></html>'),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];

        yield 'Should not index but should delete because the meta robots tag contains "noindex"' => [
            Request::create('/foobar'),
            new Response('<html><head><meta name="robots" content="noindex,nofollow"/></head><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"searchIndexer":"","protected":false,"groups":[],"fePreview":false}</script></body></html>', 200),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            true,
        ];

        yield 'Should not index and delete because the meta robots tag contains "noindex" and response is unsuccessful' => [
            Request::create('/foobar'),
            new Response('<html><head><meta name="robots" content="noindex,nofollow"/></head><body><script type="application/ld+json">{"@context":"https:\/\/contao.org\/","@type":"Page","pageId":2,"searchIndexer":"","protected":false,"groups":[],"fePreview":false}</script></body></html>', 500),
            SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
            false,
            false,
        ];

        // From the unsuccessful responses only the 404 and 410 status codes should
        // execute a deletion.
        for ($status = 400; $status < 600; ++$status) {
            if (\in_array($status, [Response::HTTP_NOT_FOUND, Response::HTTP_GONE], true)) {
                continue;
            }

            yield 'Should be skipped because the response status ('.$status.') is not successful and not a "not found" or "gone" response' => [
                Request::create('/foobar'),
                new Response('', $status),
                SearchIndexListener::FEATURE_DELETE | SearchIndexListener::FEATURE_INDEX,
                false,
                false,
            ];
        }
    }
}

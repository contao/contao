<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\EventListener;

use Contao\ApiBundle\EventListener\ApiVersionListener;
use Contao\ApiBundle\Version\ApiVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ApiVersionListenerTest extends TestCase
{
    #[DataProvider('provideValidHeaders')]
    public function testResolvesVersionAndPreservesVary(string|null $header): void
    {
        $request = $this->createRequest($header);
        $listener = new ApiVersionListener(new RequestStack());
        $kernel = $this->createStub(HttpKernelInterface::class);
        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
        $version = $request->attributes->get(ApiVersion::CONTEXT_KEY);

        $this->assertInstanceOf(ApiVersion::class, $version);
        $this->assertSame($header ?? new \DateTimeImmutable('today', new \DateTimeZone('UTC'))->format('Y-m-d'), (string) $version);

        $response = new Response(headers: ['Vary' => 'Accept, Accept-Language']);
        $listener->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        $this->assertSame((string) $version, $response->headers->get(ApiVersion::HEADER));
        $this->assertSame(['Accept', 'Accept-Language', ApiVersion::HEADER], $response->getVary());
    }

    public static function provideValidHeaders(): iterable
    {
        yield [null];
        yield ['2024-02-29'];
        yield ['1900-01-01'];
        yield [new \DateTimeImmutable('today', new \DateTimeZone('UTC'))->format('Y-m-d')];
    }

    #[DataProvider('provideInvalidHeaders')]
    public function testRejectsInvalidHeadersBeforeApiExecution(array $headers): void
    {
        $request = $this->createRequest(null);
        $request->headers->set(ApiVersion::HEADER, $headers);

        $this->expectException(BadRequestHttpException::class);

        new ApiVersionListener(new RequestStack())->onKernelRequest(new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));
    }

    public static function provideInvalidHeaders(): iterable
    {
        yield [['']];
        yield [['today']];
        yield [['2026-02-30']];
        yield [['9999-12-31']];
        yield [['2024-02-29', '2024-02-29']];
    }

    public function testIgnoresNonApiRequests(): void
    {
        $request = Request::create('/');
        $request->headers->set(ApiVersion::HEADER, 'invalid');

        $kernel = $this->createStub(HttpKernelInterface::class);
        $listener = new ApiVersionListener(new RequestStack());
        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $response = new Response();
        $listener->onKernelResponse(new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response));

        $this->assertFalse($request->attributes->has(ApiVersion::CONTEXT_KEY));
        $this->assertFalse($response->headers->has(ApiVersion::HEADER));
        $this->assertSame([], $response->getVary());
    }

    public function testHandlesSubrequestsAndDoesNotReparseHeadersDuringErrorHandling(): void
    {
        $kernel = $this->createStub(HttpKernelInterface::class);
        $listener = new ApiVersionListener(new RequestStack());
        $request = $this->createRequest('2024-02-29');
        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));
        $version = $request->attributes->get(ApiVersion::CONTEXT_KEY);
        $this->assertInstanceOf(ApiVersion::class, $version);

        $request->attributes->set('exception', new BadRequestHttpException());
        $request->headers->set(ApiVersion::HEADER, 'invalid');

        $listener->onKernelRequest(new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST));

        $this->assertSame($version, $request->attributes->get(ApiVersion::CONTEXT_KEY));
    }

    public function testInvalidVersionErrorResponsesStillVaryByHeader(): void
    {
        $request = $this->createRequest('invalid');
        $response = new Response(status: 400);
        new ApiVersionListener(new RequestStack())->onKernelResponse(new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response));

        $this->assertSame([ApiVersion::HEADER], $response->getVary());
        $this->assertFalse($response->headers->has(ApiVersion::HEADER));
    }

    public function testErrorSubrequestsInheritTheResolvedParentVersion(): void
    {
        $parent = $this->createRequest('2024-02-29');
        $version = new ApiVersion('2024-02-29');
        $parent->attributes->set(ApiVersion::CONTEXT_KEY, $version);
        $error = $parent->duplicate(attributes: ['exception' => new \RuntimeException(), '_api_respond' => true]);
        $requests = new RequestStack();
        $requests->push($parent);
        $requests->push($error);

        $this->assertFalse($error->attributes->has(ApiVersion::CONTEXT_KEY));

        new ApiVersionListener($requests)->onKernelRequest(new RequestEvent($this->createStub(HttpKernelInterface::class), $error, HttpKernelInterface::SUB_REQUEST));

        $this->assertSame($version, $error->attributes->get(ApiVersion::CONTEXT_KEY));
    }

    private function createRequest(string|null $header): Request
    {
        $request = Request::create('/contao/api/example');
        $request->attributes->set('_api_resource_class', \stdClass::class);

        if (null !== $header) {
            $request->headers->set(ApiVersion::HEADER, $header);
        }

        return $request;
    }
}

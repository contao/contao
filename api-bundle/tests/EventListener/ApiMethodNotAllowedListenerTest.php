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

use Contao\ApiBundle\EventListener\ApiMethodNotAllowedListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Loader\ClosureLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Router;

final class ApiMethodNotAllowedListenerTest extends TestCase
{
    #[DataProvider('provideUnsupportedMethods')]
    public function testThrowsMethodNotAllowedWithTheAllowedMethods(string $method, string $path, array $allowedMethods): void
    {
        $request = $this->createRequest($method, $path);

        $router = $this->createRouter();
        $context = $router->getContext()->fromRequest($request);

        try {
            $this->createListener($router)->onKernelRequest($this->createEvent($request));
            $this->fail('Expected a MethodNotAllowedHttpException.');
        } catch (MethodNotAllowedHttpException $exception) {
            $this->assertSame(['Allow' => implode(', ', $allowedMethods)], $exception->getHeaders());
        }

        $this->assertSame($context, $router->getContext());
        $this->assertSame($method, $router->getContext()->getMethod());
        $this->assertTrue($request->attributes->getBoolean('_api_respond'));
    }

    public static function provideUnsupportedMethods(): iterable
    {
        yield 'item' => ['PUT', '/contao/api/page/2', ['GET', 'HEAD', 'PATCH']];

        yield 'collection' => ['DELETE', '/contao/api/page', ['GET', 'HEAD', 'POST']];

        yield 'API root' => ['POST', '/contao/api', ['GET', 'HEAD']];
    }

    public function testThrowsNotFoundForUnknownApiPaths(): void
    {
        $request = $this->createRequest('GET', '/contao/api/does-not-exist');

        $this->expectException(NotFoundHttpException::class);

        $this->createListener($this->createRouter())->onKernelRequest($this->createEvent($request));
    }

    #[DataProvider('provideIgnoredRequests')]
    public function testIgnoresOtherRequests(string $path, string $route): void
    {
        $request = $this->createRequest('PUT', $path, $route);

        $this->createListener($this->createRouter())->onKernelRequest($this->createEvent($request));

        $this->assertFalse($request->attributes->has('_api_respond'));
    }

    public static function provideIgnoredRequests(): iterable
    {
        yield 'back end path' => ['/contao/does-not-exist', 'contao_backend_fallback'];

        yield 'path starting with the API prefix' => ['/contao/apikey', 'contao_backend_fallback'];

        yield 'API route' => ['/contao/api/page/2', 'contao_api_page_get'];
    }

    private function createListener(Router $router): ApiMethodNotAllowedListener
    {
        return new ApiMethodNotAllowedListener($router, '/contao/api');
    }

    private function createRouter(): Router
    {
        $routes = new RouteCollection();
        $routes->add('contao_api_root', new Route('/contao/api', methods: ['GET', 'HEAD']));
        $routes->add('contao_api_page_get_collection', new Route('/contao/api/page', methods: ['GET']));
        $routes->add('contao_api_page_post', new Route('/contao/api/page', methods: ['POST']));
        $routes->add('contao_api_page_get', new Route('/contao/api/page/{id}', methods: ['GET']));
        $routes->add('contao_api_page_patch', new Route('/contao/api/page/{id}', methods: ['PATCH']));
        $routes->add('contao_backend_fallback', new Route('/contao/{parameters}', requirements: ['parameters' => '.*']), -1000);

        return new Router(new ClosureLoader(), static fn (): RouteCollection => $routes);
    }

    private function createRequest(string $method, string $path, string $route = 'contao_backend_fallback'): Request
    {
        $request = Request::create($path, $method);
        $request->attributes->set('_route', $route);

        return $request;
    }

    private function createEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }
}

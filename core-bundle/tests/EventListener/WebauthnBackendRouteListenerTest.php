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

use Contao\CoreBundle\EventListener\WebauthnBackendRouteListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class WebauthnBackendRouteListenerTest extends TestCase
{
    /**
     * @dataProvider routeProvider
     */
    public function testSetsTheCorrectScope(string $requestRoute, string|null $resultingScope): void
    {
        $request = new Request();
        $request->attributes->set('_route', $requestRoute);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $routes = [
            'webauthn.controller.creation.request.contao_backend_add_authenticator',
            'webauthn.controller.creation.response.contao_backend_add_authenticator',
            'webauthn.controller.security.contao_backend.request.options',
            'webauthn.controller.security.contao_backend.request.result',
        ];

        (new WebauthnBackendRouteListener($routes))($event);

        $this->assertSame($resultingScope, $request->attributes->get('_scope'));
    }

    public static function routeProvider(): iterable
    {
        yield ['webauthn.controller.creation.request.contao_backend_add_authenticator', 'backend'];
        yield ['webauthn.controller.creation.response.contao_backend_add_authenticator', 'backend'];
        yield ['webauthn.controller.security.contao_backend.request.options', 'backend'];
        yield ['webauthn.controller.security.contao_backend.request.result', 'backend'];
        yield ['contao_frontend', null];
    }
}

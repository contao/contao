<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

final class ApiMethodNotAllowedListener
{
    private const string FALLBACK_ROUTE = 'contao_backend_fallback';

    private const array METHODS = [
        Request::METHOD_GET,
        Request::METHOD_HEAD,
        Request::METHOD_POST,
        Request::METHOD_PUT,
        Request::METHOD_PATCH,
        Request::METHOD_DELETE,
    ];

    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $apiPrefix,
    ) {
    }

    #[AsEventListener(KernelEvents::REQUEST, priority: 30)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (self::FALLBACK_ROUTE !== $request->attributes->get('_route')) {
            return;
        }

        $path = $request->getPathInfo();

        if ($path !== $this->apiPrefix && !str_starts_with($path, $this->apiPrefix.'/')) {
            return;
        }

        $request->attributes->set('_api_respond', true);

        $allowedMethods = $this->getAllowedMethods($path);

        if ([] === $allowedMethods) {
            throw new NotFoundHttpException(\sprintf('No route found for "%s %s".', $request->getMethod(), $path));
        }

        throw new MethodNotAllowedHttpException($allowedMethods, \sprintf('No route found for "%s %s": Method Not Allowed (Allow: %s).', $request->getMethod(), $path, implode(', ', $allowedMethods)));
    }

    /**
     * @return list<string>
     */
    private function getAllowedMethods(string $path): array
    {
        $context = $this->router->getContext();
        $allowedMethods = [];

        try {
            foreach (self::METHODS as $method) {
                $this->router->setContext((clone $context)->setMethod($method));

                try {
                    $route = $this->router->match($path)['_route'] ?? null;
                } catch (ExceptionInterface) {
                    continue;
                }

                if (self::FALLBACK_ROUTE !== $route) {
                    $allowedMethods[] = $method;
                }
            }
        } finally {
            $this->router->setContext($context);
        }

        return $allowedMethods;
    }
}

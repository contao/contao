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

use Contao\ApiBundle\Version\ApiVersion;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiVersionListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[AsEventListener(KernelEvents::REQUEST, priority: 28)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isApiRequest($request)) {
            return;
        }

        // Symfony replaces request attributes for error subrequests. Preserve the
        // resolved version without validating an invalid header a second time.
        if ($request->attributes->has('exception')) {
            $version = $this->requestStack->getParentRequest()?->attributes->get(ApiVersion::CONTEXT_KEY);

            if ($version instanceof ApiVersion) {
                $request->attributes->set(ApiVersion::CONTEXT_KEY, $version);
            }

            return;
        }

        $request->attributes->set(ApiVersion::CONTEXT_KEY, $this->resolveVersion($request));
    }

    #[AsEventListener(KernelEvents::RESPONSE, priority: -128)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isApiRequest($request)) {
            return;
        }

        $response = $event->getResponse();
        $response->setVary(ApiVersion::HEADER, false);

        $version = $request->attributes->get(ApiVersion::CONTEXT_KEY);

        if ($version instanceof ApiVersion) {
            $response->headers->set(ApiVersion::HEADER, (string) $version);
        }
    }

    private function isApiRequest(Request $request): bool
    {
        return $request->attributes->has('_api_resource_class')
            || $request->attributes->has('_api_operation')
            || $request->attributes->getBoolean('_api_respond');
    }

    private function resolveVersion(Request $request): ApiVersion
    {
        $headers = $request->headers->all(ApiVersion::HEADER);

        if (\count($headers) > 1) {
            throw new BadRequestHttpException('The Contao-Api-Version header must contain exactly one date.');
        }

        $today = ApiVersion::today();

        if ([] === $headers) {
            return $today;
        }

        try {
            $version = new ApiVersion($headers[0]);
        } catch (\InvalidArgumentException $exception) {
            throw new BadRequestHttpException('The Contao-Api-Version header must be a valid date in YYYY-MM-DD format.', $exception);
        }

        if ($version->toDateTime() > $today->toDateTime()) {
            throw new BadRequestHttpException('The Contao-Api-Version header must not contain a future date.');
        }

        return $version;
    }
}

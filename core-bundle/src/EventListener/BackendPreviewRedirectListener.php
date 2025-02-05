<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 */
#[AsEventListener]
class BackendPreviewRedirectListener
{
    public function __construct(private readonly ScopeMatcher $scopeMatcher)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (
            !$request->attributes->has('_preview')
            || true !== $request->attributes->get('_preview')
            || $this->scopeMatcher->isFrontendRequest($request)
            || ($request->attributes->has('_allow_preview') && true === $request->attributes->get('_allow_preview'))
        ) {
            return;
        }

        $event->setResponse(new RedirectResponse($request->getSchemeAndHttpHost().$request->getBasePath().$request->getPathInfo().(null !== ($qs = $request->server->get('QUERY_STRING')) ? '?'.$qs : '')));
    }
}

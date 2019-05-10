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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class RemembermeReloadListener
{
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (
            !$event->isMasterRequest()
            || !$request->isMethod(Request::METHOD_GET)
            || !$request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME)
        ) {
            return;
        }

        $response = new RedirectResponse($request->getRequestUri(), Response::HTTP_FOUND);
        $response->headers->setCookie($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME));

        $event->setResponse($response);
    }
}

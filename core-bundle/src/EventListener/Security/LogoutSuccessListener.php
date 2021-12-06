<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Security;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;

class LogoutSuccessListener
{
    private HttpUtils $httpUtils;
    private ScopeMatcher $scopeMatcher;

    /**
     * @internal
     */
    public function __construct(HttpUtils $httpUtils, ScopeMatcher $scopeMatcher)
    {
        $this->httpUtils = $httpUtils;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function __invoke(LogoutEvent $event): void
    {
        if (null !== $event->getResponse()) {
            return;
        }

        $request = $event->getRequest();

        if ($this->scopeMatcher->isBackendRequest($request)) {
            $event->setResponse($this->httpUtils->createRedirectResponse($request, 'contao_backend_login'));

            return;
        }

        if ($targetUrl = (string) $request->request->get('_target_path')) {
            $event->setResponse($this->httpUtils->createRedirectResponse($request, $targetUrl));

            return;
        }

        if ($targetUrl = (string) $request->query->get('redirect')) {
            $event->setResponse($this->httpUtils->createRedirectResponse($request, $targetUrl));

            return;
        }

        if ($targetUrl = (string) $request->headers->get('Referer')) {
            $event->setResponse($this->httpUtils->createRedirectResponse($request, $targetUrl));
        }
    }
}

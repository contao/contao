<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Session;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionFactory implements SessionFactoryInterface
{
    public function __construct(
        readonly private SessionFactoryInterface $inner,
        readonly private SessionBagInterface $backendBag,
        readonly private SessionBagInterface $frontendBag,
        readonly private SessionBagInterface $backendPopupBag,
        readonly private ScopeMatcher $scopeMatcher,
        readonly private RequestStack $requestStack,
    ) {
    }

    public function createSession(): SessionInterface
    {
        $session = $this->inner->createSession();
        $request = $this->requestStack->getMainRequest();

        if ($this->scopeMatcher->isBackendRequest($request) && $request->query->has('popup')) {
            $session->registerBag($this->backendPopupBag);
        } else {
            $session->registerBag($this->backendBag);
        }

        $session->registerBag($this->frontendBag);

        return $session;
    }
}

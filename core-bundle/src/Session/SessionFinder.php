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

class SessionFinder
{
    public function __construct(
        readonly private ScopeMatcher $scopeMatcher,
        readonly private RequestStack $requestStack,
    ) {
    }

    public function getSession(): SessionBagInterface
    {
        if ($this->scopeMatcher->isBackendRequest()) {
            $name = 'contao_backend';
            $request = $this->requestStack->getCurrentRequest();

            if ($request?->get('popup')) {
                $name = 'contao_backend_popup';
            }

            return $this->requestStack->getSession()->getBag($name);
        }

        return $this->requestStack->getSession()->getBag('contao_frontend');
    }
}

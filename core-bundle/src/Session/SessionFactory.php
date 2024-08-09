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
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionFactory implements SessionFactoryInterface
{
    public function __construct(
        readonly private SessionFactoryInterface $inner,
        readonly private RequestStack $requestStack,
        readonly private ScopeMatcher $scopeMatcher,
    ) {
    }

    public function createSession(): SessionInterface
    {
        $session = $this->inner->createSession();
        $session->registerBag($this->getBackendBag());
        $session->registerBag($this->getFrontendBag());

        return $session;
    }

    private function getBackendBag(): SessionBagInterface
    {
        $storageKey = '_contao_be_attributes';
        $request = $this->requestStack->getMainRequest();

        // Use a different storage key in the back end popup (see #7176)
        if ($this->scopeMatcher->isBackendRequest($request) && $request->query->has('popup')) {
            $storageKey .= '_popup';
        }

        $bag = new ArrayAttributeBag($storageKey);
        $bag->setName('contao_backend');

        return $bag;
    }

    private function getFrontendBag(): SessionBagInterface
    {
        $bag = new ArrayAttributeBag('_contao_fe_attributes');
        $bag->setName('contao_frontend');

        return $bag;
    }
}

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

use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionFactory implements SessionFactoryInterface
{
    public function __construct(readonly private SessionFactoryInterface $inner)
    {
    }

    public function createSession(): SessionInterface
    {
        $session = $this->inner->createSession();

        $backendBag = new ArrayAttributeBag('_contao_be_attributes');
        $backendBag->setName('contao_backend');

        $session->registerBag($backendBag);

        $backendPopupBag = new ArrayAttributeBag('_contao_be_popup_attributes');
        $backendPopupBag->setName('contao_backend_popup');

        $session->registerBag($backendPopupBag);

        $frontendBag = new ArrayAttributeBag('_contao_fe_attributes');
        $frontendBag->setName('contao_frontend');

        $session->registerBag($frontendBag);

        return $session;
    }
}

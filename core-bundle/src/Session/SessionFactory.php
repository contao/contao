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

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionFactory implements SessionFactoryInterface
{
    public function __construct(
        readonly private SessionFactoryInterface $inner,
        readonly private SessionBagInterface $backendBag,
        readonly private SessionBagInterface $frontendBag,
    ) {
    }

    public function createSession(): SessionInterface
    {
        $session = $this->inner->createSession();

        $session->registerBag($this->backendBag);
        $session->registerBag($this->frontendBag);

        return $session;
    }
}

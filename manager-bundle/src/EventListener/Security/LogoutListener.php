<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\EventListener\Security;

use Contao\ManagerBundle\HttpKernel\JwtManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    /**
     * @internal Do not inherit from this class; decorate the "contao_manager.security.logout_handler" service instead
     */
    public function __construct(private JwtManager|null $jwtManager = null)
    {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response instanceof Response) {
            return;
        }

        $this->jwtManager?->clearResponseCookie($response);
    }
}

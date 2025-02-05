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

use Contao\CoreBundle\Exception\ServiceUnavailableException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * The priority must be lower than the Symfony route listener (defaults to 32) and
 * lower than the Symfony firewall listener (defaults to 8).
 *
 * @internal
 */
#[AsEventListener(priority: 6)]
class ServiceUnavailableListener
{
    public function __construct(private readonly ScopeMatcher $scopeMatcher)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (
            !$this->scopeMatcher->isFrontendMainRequest($event)
            || $request->attributes->get('_preview', false)
            || $request->attributes->get('_bypass_maintenance', false)
        ) {
            return;
        }

        $pageModel = $request->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            return;
        }

        $pageModel->loadDetails();

        if ($pageModel->maintenanceMode) {
            throw new ServiceUnavailableException('This website is in maintenance mode.');
        }
    }
}

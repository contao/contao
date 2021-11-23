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
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ServiceUnavailableListener
{
    private ScopeMatcher $scopeMatcher;

    public function __construct(ScopeMatcher $scopeMatcher)
    {
        $this->scopeMatcher = $scopeMatcher;
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();
        $pageModel = $request->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            return;
        }

        $pageModel->loadDetails();

        if ($pageModel->maintenanceMode) {
            throw new ServiceUnavailableException(sprintf('Domain %s is in maintenance mode', $pageModel->dns));
        }
    }
}

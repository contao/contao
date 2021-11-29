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
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @internal
 */
class ServiceUnavailableListener
{
    public const JWT_ATTRIBUTE = 'bypass_maintenance';

    private ScopeMatcher $scopeMatcher;
    private ?JwtManager $jwtManager;

    public function __construct(ScopeMatcher $scopeMatcher, JwtManager $jwtManager = null)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->jwtManager = $jwtManager;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (
            !$this->scopeMatcher->isFrontendMainRequest($event)
            || $request->attributes->get('_preview', false)
            || $this->isDisabledByJwt($request)
        ) {
            return;
        }

        $pageModel = $request->attributes->get('pageModel');

        if (!$pageModel instanceof PageModel) {
            return;
        }

        $pageModel->loadDetails();

        if ($pageModel->maintenanceMode) {
            throw new ServiceUnavailableException(sprintf('Domain %s is in maintenance mode', $pageModel->dns));
        }
    }

    private function isDisabledByJwt(Request $request): bool
    {
        if (null === $this->jwtManager) {
            return false;
        }

        $data = $this->jwtManager->parseRequest($request);

        return (bool) ($data[self::JWT_ATTRIBUTE] ?? false);
    }
}

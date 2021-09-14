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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * This makes sure that any redirect response in the Contao frontend scope is at least path-absolute (see #3065).
 *
 * @ServiceTag("kernel.event_listener")
 */
class MakeRedirectResponseUrlAbsoluteListener
{
    private $scopeMatcher;

    public function __construct(ScopeMatcher $scopeMatcher)
    {
        $this->scopeMatcher = $scopeMatcher;
    }

    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$this->scopeMatcher->isFrontendMasterRequest($event) || !$response instanceof RedirectResponse) {
            return;
        }

        $url = $response->getTargetUrl();

        if ('/' === $url[0] || null !== parse_url($url, PHP_URL_SCHEME)) {
            return;
        }

        $response->setTargetUrl('/'.$url);
    }
}

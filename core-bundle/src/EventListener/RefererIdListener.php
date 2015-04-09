<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Adds the referer id to the current request.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class RefererIdListener extends ScopeAwareListener
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * Constructor.
     *
     * @param CsrfTokenManagerInterface $tokenManager  The token manager service
     */
    public function __construct(CsrfTokenManagerInterface $tokenManger)
    {
        $this->tokenManager = $tokenManger;
    }

    /**
     * Adds the referer id to the request
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->isBackendMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        /** @var CsrfToken $token */
        $token = $this->tokenManager->refreshToken('contao_referer_id');

        $request->attributes->set('_contao_referer_id', $token->getValue());
    }
}

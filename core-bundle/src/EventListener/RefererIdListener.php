<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Adds the referer ID to the current request.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class RefererIdListener
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * Constructor.
     *
     * @param CsrfTokenManagerInterface $tokenManager
     * @param ScopeMatcher              $scopeMatcher
     */
    public function __construct(CsrfTokenManagerInterface $tokenManager, ScopeMatcher $scopeMatcher)
    {
        $this->tokenManager = $tokenManager;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Adds the referer ID to the request.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$this->scopeMatcher->isBackendMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        /** @var CsrfToken $token */
        $token = $this->tokenManager->refreshToken('contao_referer_id');

        $request->attributes->set('_contao_referer_id', $token->getValue());
    }
}

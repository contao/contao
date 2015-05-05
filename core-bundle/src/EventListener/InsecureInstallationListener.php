<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\InsecureInstallationException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Ensures that the document root is secure.
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsecureInstallationListener
{
    /**
     * @var array
     */
    protected $localIps = ['127.0.0.1', 'fe80::1', '::1'];

    /**
     * Throws an exception if the document root is insecure.
     *
     * @param GetResponseEvent $event The event object
     *
     * @throws InsecureInstallationException If the document root is insecure
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // Skip the check in the install tool
        if ('contao_backend_install' === $request->attributes->get('_route')) {
            return;
        }

        // Skip the check on localhost
        if (in_array($request->getClientIp(), $this->localIps)) {
            return;
        }

        // The document root does not contain /web
        if ('/web' !== substr($request->getBasePath(), -4)) {
            return;
        }

        throw new InsecureInstallationException(
            'Your installation is not secure. Please set the document root to the /web subfolder.'
        );
    }
}

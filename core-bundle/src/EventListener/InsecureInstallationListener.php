<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Validates the Installation
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Dominik Tomasi <https://github.com/dtomasi>
 */
class InsecureInstallationListener
{
    /**
     * @var array
     */
    protected $secureClientIps = array('127.0.0.1', 'fe80::1', '::1');

    /**
     * Validates the installation.
     *
     * @param GetResponseEvent $event
     *
     * @throws InsecureInstallationException   If the document root is not set correctly
     * @throws IncompleteInstallationException If the installation has not been completed
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (null === $request || 'contao_backend_install' === $request->attributes->get('_route')) {
            return;
        }

        // Show the "insecure document root" message
        if (!in_array($request->getClientIp(), $this->secureClientIps) && '/web' === substr($request->getBasePath(), -4)
        ) {
            throw new InsecureInstallationException(
                'Your installation is not secure. Please set the document root to the /web subfolder.'
            );
        }
    }
}

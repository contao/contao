<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
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
    private $localIps = ['127.0.0.1', 'fe80::1', '::1'];

    /**
     * Throws an exception if the document root is insecure.
     *
     * @param GetResponseEvent $event
     *
     * @throws InsecureInstallationException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // Skip the check on localhost
        if (in_array($request->getClientIp(), $this->localIps)) {
            return;
        }

        // The document root is not in a subdirectory
        if ('' === $request->getBasePath()) {
            return;
        }

        throw new InsecureInstallationException(
            'Your installation is not secure. Please set the document root to the /web subfolder.'
        );
    }
}

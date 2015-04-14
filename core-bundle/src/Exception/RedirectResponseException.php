<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Sends a redirect response and stops the progam flow.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class RedirectResponseException extends AbstractResponseException
{
    /**
     * Constructor.
     *
     * @param string $location The target URL
     * @param int    $status   The response status code (defaults to 204)
     * @param array  $headers  An array of response headers
     */
    public function __construct($location, $status = 303, $headers = [])
    {
        parent::__construct(new RedirectResponse($location, $status, $headers));
    }
}

<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a redirect response and stops the program flow.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class AjaxRedirectResponseException extends AbstractResponseException
{
    /**
     * Constructor.
     *
     * @param string $location The target URL
     * @param int    $status   The response status code (defaults to 204)
     * @param array  $headers  An array of response headers
     */
    public function __construct($location, $status = 204, $headers = [])
    {
        $headers = array_merge(
            [
                'X-Ajax-Location' => $location
            ],
            $headers
        );

        parent::__construct(new Response('', $status, $headers));
    }
}

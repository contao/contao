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
 * Sends a response and stops the program flow.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ResponseException extends AbstractResponseException
{
    /**
     * Constructor.
     *
     * @param mixed  $response The response string or object
     * @param int    $status   The response status code (defaults to 204)
     * @param array  $headers  An array of response headers
     */
    public function __construct($response, $status = 200, $headers = [])
    {
        if (!$response instanceof Response) {
            $response = new Response($response, $status, $headers);
        }

        parent::__construct($response);
    }
}

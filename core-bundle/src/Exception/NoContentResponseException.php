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
 * Exception to send an empty response and exit the execution in the Contao workflow.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class NoContentResponseException extends AbstractResponseException
{
    /**
     * Factory method for creating the Response instance internally.
     *
     * Example:
     *
     *     throw NoContentResponseException::create();
     *
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     *
     * @return ResponseException
     */
    public static function create($status = 204, $headers = array())
    {
        return new static(new Response('', $status, $headers));
    }
}

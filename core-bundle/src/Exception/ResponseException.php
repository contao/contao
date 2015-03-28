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
 * Exception to send a response and exit the execution in the Contao workflow.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ResponseException extends AbstractResponseException
{
    /**
     * Factory method for creating the Response instance internally.
     *
     * Example:
     *
     *     throw ResponseException::create('Teapots can not brew coffee!', 418);
     *
     * @param mixed $content The response content
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     *
     * @return ResponseException
     */
    public static function create($content = '', $status = 200, $headers = array())
    {
        return new static(new Response($content, $status, $headers));
    }
}

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
     * Factory method creating an ajax Response instance internally.
     *
     * Example:
     *
     *     throw RedirectResponseException::create('https://example.org/target.html');
     *
     * @param string $location The target URL
     * @param int    $status   The response status code (defaults to 204)
     * @param array  $headers  An array of response headers
     *
     * @return RedirectResponseException
     */
    public static function create($location, $status = 204, $headers = array())
    {
        return new static(new Response('', $status, array_merge(['X-Ajax-Location' => $location], $headers)));
    }
}

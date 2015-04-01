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
 * Exception to send a redirect response and exit the execution in the Contao workflow.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class RedirectResponseException extends AbstractResponseException
{
    /**
     * Factory method creating an Response instance internally.
     *
     * Example:
     *
     *     throw RedirectResponseException::create('https://example.org/target.html');
     *
     * @param string $location The target URL
     * @param int    $status   The response status code (defaults to 303)
     * @param array  $headers  An array of response headers
     *
     * @return RedirectResponseException
     */
    public static function create($location, $status = 303, $headers = array())
    {
        return new static(new RedirectResponse($location, $status, $headers));
    }
}

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
 * Initializes a response exception with an Ajax compatible redirect response.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AjaxRedirectResponseException extends ResponseException
{
    /**
     * Constructor.
     *
     * @param string     $location The target URL
     * @param int        $status   The response status code (defaults to 204)
     * @param \Exception $previous The previous exception
     */
    public function __construct($location, $status = 204, \Exception $previous = null)
    {
        parent::__construct(new Response($location, $status, ['X-Ajax-Location' => $location]), $previous);
    }
}

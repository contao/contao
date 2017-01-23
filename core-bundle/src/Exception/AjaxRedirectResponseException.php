<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
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
     * @param string          $location
     * @param int             $status
     * @param \Exception|null $previous
     */
    public function __construct($location, $status = 302, \Exception $previous = null)
    {
        parent::__construct(new Response($location, $status, ['X-Ajax-Location' => $location]), $previous);
    }
}

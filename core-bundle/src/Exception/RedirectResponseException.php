<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Initializes a response exception with a redirect response.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class RedirectResponseException extends ResponseException
{
    /**
     * Constructor.
     *
     * @param string          $location
     * @param int             $status
     * @param \Exception|null $previous
     */
    public function __construct($location, $status = 303, \Exception $previous = null)
    {
        parent::__construct(new RedirectResponse($location, $status), $previous);
    }
}

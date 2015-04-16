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
 * Initializes a response exception with an empty response.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class NoContentResponseException extends ResponseException
{
    /**
     * Constructor.
     *
     * @param \Exception $previous The previous exception
     */
    public function __construct(\Exception $previous = null)
    {
        parent::__construct(new Response('', 204), $previous);
    }
}
